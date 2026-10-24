<?php

namespace App\Services;

use App\Exceptions\PromotionImportValidationException;
use App\Models\ProductCategoryMultiplier;
use App\Models\PromotionItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class PromotionItemCategoryUpdateService
{
    /**
     * Update promotion items' product_category_id from uploaded Excel or CSV.
     *
     * @param UploadedFile $file
     * @return array{total_read: int, updated: int, created_categories: int}
     * @throws PromotionImportValidationException
     */
    public function updateFromExcel(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        if ($extension === 'csv' || $extension === 'txt') {
            $reader = new CsvReader();
        } else {
            $reader = new XlsxReader();
        }

        $reader->open($filePath);

        $headerMap = [];
        $isFirstRow = true;
        $totalRead = 0;
        $rowErrors = [];

        // Preload categories
        $categories = ProductCategoryMultiplier::all();
        $categoryLookup = [];
        foreach ($categories as $cat) {
            $categoryLookup[strtolower(trim($cat->name))] = $cat->id;
        }

        // Preload promotion items by barcode and lot_serial_number
        $itemsByBarcode = PromotionItem::whereNotNull('barcode')
            ->pluck('id', 'barcode')
            ->toArray();

        $itemsByLot = PromotionItem::whereNotNull('lot_serial_number')
            ->pluck('id', 'lot_serial_number')
            ->toArray();

        $pendingUpdates = []; // itemId => categoryId
        $newCategoriesCount = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            $rowNumber = 0;
            foreach ($sheet->getRowIterator() as $row) {
                $rowNumber++;
                $cells = $row->getCells();
                $rowValues = array_map(fn ($cell) => trim((string) $cell->getValue()), $cells);

                // If entire row is empty, skip
                if (empty(array_filter($rowValues, fn ($v) => $v !== ''))) {
                    continue;
                }

                if ($isFirstRow) {
                    $headerMap = $this->buildHeaderMap($rowValues);
                    $isFirstRow = false;
                    continue;
                }

                $totalRead++;
                $rowData = $this->mapRow($rowValues, $headerMap);

                $barcode = trim($rowData['barcode'] ?? '');
                $lotSerialNumber = trim($rowData['lot_serial_number'] ?? '');
                $categoryName = trim($rowData['product_category'] ?? '');

                // Check for summary or empty identification row
                if ($barcode === '' && $lotSerialNumber === '') {
                    continue;
                }

                if (preg_match('/promotion\s*\(\d+\)/i', $barcode)) {
                    continue;
                }

                $hasRowError = false;

                // Rule: If not have product_category value, reject the import!
                if ($categoryName === '') {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'barcode' => $barcode ?: $lotSerialNumber,
                        'product' => $rowData['product'] ?? '',
                        'error' => "Product category is missing (Product Category အချက်အလက် မပါရှိပါ).",
                    ];
                    $hasRowError = true;
                }

                // Match item in database
                $itemId = null;
                if ($barcode !== '' && isset($itemsByBarcode[$barcode])) {
                    $itemId = $itemsByBarcode[$barcode];
                } elseif ($lotSerialNumber !== '' && isset($itemsByLot[$lotSerialNumber])) {
                    $itemId = $itemsByLot[$lotSerialNumber];
                } else {
                    $identifier = $barcode ?: $lotSerialNumber;
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'barcode' => $identifier,
                        'product' => $rowData['product'] ?? '',
                        'error' => "Promotion item '{$identifier}' not found in database (စနစ်ထဲတွင် ပစ္စည်း ရှာမတွေ့ပါ).",
                    ];
                    $hasRowError = true;
                }

                if (!$hasRowError) {
                    // Check if category exists, if not create it
                    $lowerCat = strtolower($categoryName);
                    if (isset($categoryLookup[$lowerCat])) {
                        $categoryId = $categoryLookup[$lowerCat];
                    } else {
                        $newCategory = ProductCategoryMultiplier::create([
                            'name' => $categoryName,
                            'multiplier' => 1.0,
                        ]);
                        $categoryLookup[$lowerCat] = $newCategory->id;
                        $categoryId = $newCategory->id;
                        $newCategoriesCount++;
                    }

                    $pendingUpdates[$itemId] = $categoryId;
                }
            }

            break; // First sheet only
        }

        $reader->close();

        // If any error encountered, reject entire import
        if (!empty($rowErrors)) {
            throw new PromotionImportValidationException($rowErrors);
        }

        // Apply updates in a transaction
        $updatedCount = 0;
        DB::transaction(function () use ($pendingUpdates, &$updatedCount) {
            foreach ($pendingUpdates as $itemId => $categoryId) {
                PromotionItem::where('id', $itemId)->update([
                    'product_category_id' => $categoryId,
                    'updated_at' => now(),
                ]);
                $updatedCount++;
            }
        });

        return [
            'total_read' => $totalRead,
            'updated' => $updatedCount,
            'created_categories' => $newCategoriesCount,
        ];
    }

    /**
     * Map header columns.
     */
    protected function buildHeaderMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $header) {
            $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$header));

            if (str_contains($normalized, 'barcode')) {
                $map['barcode'] = $index;
            } elseif (str_contains($normalized, 'lotserial') || str_contains($normalized, 'lot') || str_contains($normalized, 'serial')) {
                $map['lot_serial_number'] = $index;
            } elseif (str_contains($normalized, 'category')) {
                $map['product_category'] = $index;
            } elseif ($normalized === 'product' || str_contains($normalized, 'productname')) {
                $map['product'] = $index;
            }
        }

        return $map;
    }

    /**
     * Map row data.
     */
    protected function mapRow(array $rowValues, array $headerMap): array
    {
        $data = [
            'barcode' => '',
            'lot_serial_number' => '',
            'product_category' => '',
            'product' => '',
        ];

        foreach ($headerMap as $key => $index) {
            $data[$key] = $rowValues[$index] ?? '';
        }

        return $data;
    }
}
