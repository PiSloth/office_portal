<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\ProductCategoryMultiplier;
use App\Models\PromotionItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class PromotionItemImportService
{
    /**
     * Import promotion items from an uploaded file (xlsx, xls, csv).
     *
     * @param UploadedFile $file
     * @param bool $updateExisting If true, existing items by barcode/lot are updated instead of rejected.
     * @return array{total_read: int, imported: int, updated: int, skipped: int}
     * @throws \Exception
     */
    public function import(UploadedFile $file, bool $updateExisting = true): array
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
        $skipped = 0;

        // Preload branches for fast lookup (name lower => id, and code lower => id)
        $branches = Branch::all();
        $branchLookup = [];
        foreach ($branches as $branch) {
            $branchLookup[strtolower(trim($branch->name))] = $branch->id;
            if ($branch->code) {
                $branchLookup[strtolower(trim($branch->code))] = $branch->id;
            }
        }

        // Preload product category multipliers for lookup
        $categories = ProductCategoryMultiplier::all();
        $categoryLookup = [];
        foreach ($categories as $cat) {
            $categoryLookup[strtolower(trim($cat->name))] = $cat->id;
        }

        // Preload existing barcodes and lot numbers to check uniqueness or update in database
        $existingBarcodes = PromotionItem::whereNotNull('barcode')
            ->pluck('id', 'barcode')
            ->mapWithKeys(fn ($id, $b) => [trim($b) => $id])
            ->toArray();

        $existingLotNumbers = PromotionItem::whereNotNull('lot_serial_number')
            ->pluck('id', 'lot_serial_number')
            ->mapWithKeys(fn ($id, $l) => [trim($l) => $id])
            ->toArray();

        // Track items encountered within the current file being imported
        $fileBarcodes = [];
        $fileLotNumbers = [];

        $rowErrors = [];
        $validItems = [];
        $itemsToUpdate = [];

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

                // Check if this row is a grouping/summary row or an actual product item
                if ($this->isGroupingOrSummaryRow($rowData)) {
                    $skipped++;
                    continue;
                }

                // If essential identifying fields are completely empty, skip
                if (empty($rowData['barcode']) && empty($rowData['lot_serial_number']) && empty($rowData['product'])) {
                    $skipped++;
                    continue;
                }

                $barcode = trim($rowData['barcode'] ?? '');
                $lotSerialNumber = trim($rowData['lot_serial_number'] ?? '');
                $hasRowError = false;

                // Validate barcode uniqueness within file
                if ($barcode !== '') {
                    if (isset($fileBarcodes[$barcode])) {
                        $prevRow = $fileBarcodes[$barcode];
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'barcode' => $barcode,
                            'product' => $rowData['product'],
                            'error' => "Duplicate barcode '{$barcode}' found in this file (Row {$prevRow} နှင့် တူညီနေပါသည်).",
                        ];
                        $hasRowError = true;
                    } else {
                        $fileBarcodes[$barcode] = $rowNumber;
                    }
                }

                // Validate lot/serial number uniqueness within file
                if ($lotSerialNumber !== '') {
                    if (isset($fileLotNumbers[$lotSerialNumber])) {
                        $prevRow = $fileLotNumbers[$lotSerialNumber];
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'barcode' => $barcode,
                            'product' => $rowData['product'],
                            'error' => "Duplicate Lot/Serial '{$lotSerialNumber}' found in this file (Row {$prevRow} နှင့် တူညီနေပါသည်).",
                        ];
                        $hasRowError = true;
                    } else {
                        $fileLotNumbers[$lotSerialNumber] = $rowNumber;
                    }
                }

                // Check against existing database records
                $existingId = null;
                if ($barcode !== '' && isset($existingBarcodes[$barcode])) {
                    $existingId = $existingBarcodes[$barcode];
                    if (!$updateExisting) {
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'barcode' => $barcode,
                            'product' => $rowData['product'],
                            'error' => "Duplicate barcode '{$barcode}' already exists in database (Barcode စနစ်ထဲတွင် ရှိပြီးသားဖြစ်နေပါသည်).",
                        ];
                        $hasRowError = true;
                    }
                }

                if ($lotSerialNumber !== '' && isset($existingLotNumbers[$lotSerialNumber])) {
                    $lotExistingId = $existingLotNumbers[$lotSerialNumber];
                    if ($existingId !== null && $existingId !== $lotExistingId) {
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'barcode' => $barcode,
                            'product' => $rowData['product'],
                            'error' => "Barcode '{$barcode}' and Lot/Serial '{$lotSerialNumber}' belong to two different existing records in database.",
                        ];
                        $hasRowError = true;
                    } else {
                        $existingId = $lotExistingId;
                    }

                    if (!$updateExisting) {
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'barcode' => $barcode,
                            'product' => $rowData['product'],
                            'error' => "Duplicate Lot/Serial '{$lotSerialNumber}' already exists in database (Lot/Serial နံပါတ် စနစ်ထဲတွင် ရှိပြီးသားဖြစ်နေပါသည်).",
                        ];
                        $hasRowError = true;
                    }
                }

                // Validate branch name
                $branchName = trim($rowData['branch_name']);
                $branchId = null;

                if ($branchName === '') {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'barcode' => $rowData['barcode'],
                        'product' => $rowData['product'],
                        'error' => "Branch name is missing (ဆိုင်ခွဲအမည် မပါရှိပါ).",
                    ];
                    $hasRowError = true;
                } else {
                    $lowerBranch = strtolower($branchName);
                    if (isset($branchLookup[$lowerBranch])) {
                        $branchId = $branchLookup[$lowerBranch];
                    } else {
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'barcode' => $rowData['barcode'],
                            'product' => $rowData['product'],
                            'error' => "Branch '{$branchName}' not found in branch records (ဆိုင်ခွဲအမည် ရှာမတွေ့ပါ).",
                        ];
                        $hasRowError = true;
                    }
                }

                // Validate product category (required)
                $categoryName = trim($rowData['product_category'] ?? '');
                $productCategoryId = null;

                if ($categoryName === '') {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'barcode' => $rowData['barcode'],
                        'product' => $rowData['product'],
                        'error' => "Product category is missing (Product Category မပါရှိပါ).",
                    ];
                    $hasRowError = true;
                } else {
                    $lowerCategory = strtolower($categoryName);
                    if (isset($categoryLookup[$lowerCategory])) {
                        $productCategoryId = $categoryLookup[$lowerCategory];
                    } else {
                        $newCategory = ProductCategoryMultiplier::create([
                            'name' => $categoryName,
                            'multiplier' => 1.0,
                        ]);
                        $categoryLookup[$lowerCategory] = $newCategory->id;
                        $productCategoryId = $newCategory->id;
                    }
                }

                if (!$hasRowError) {
                    $itemData = [
                        'barcode' => $barcode ?: null,
                        'product' => $rowData['product'] ?: null,
                        'weight' => $this->parseDecimal($rowData['weight']),
                        'gold_weight' => $this->parseDecimal($rowData['gold_weight']),
                        'dg_weight' => $this->parseDecimal($rowData['dg_weight']) ?? 0,
                        'addition_wastage' => $this->parseDecimal($rowData['addition_wastage']),
                        'total_wastage' => $this->parseDecimal($rowData['total_wastage']),
                        'labor_fee' => $this->parseDecimal($rowData['labor_fee']) ?? 0,
                        'internal_reference' => $rowData['internal_reference'] ?: null,
                        'lot_serial_number' => $lotSerialNumber ?: null,
                        'product_subgroup_name' => $rowData['product_subgroup_name'] ?: null,
                        'branch_id' => $branchId,
                        'product_category_id' => $productCategoryId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($existingId !== null) {
                        $itemsToUpdate[] = [
                            'id' => $existingId,
                            'data' => $itemData,
                        ];
                    } else {
                        $validItems[] = $itemData;
                    }
                }
            }

            // Only process the first sheet
            break;
        }

        $reader->close();

        // If there are validation errors, abort the import and return error details
        if (!empty($rowErrors)) {
            $errorCount = count($rowErrors);
            throw new \App\Exceptions\PromotionImportValidationException($rowErrors);
        }

        // Insert new items and update existing items in a transaction
        $imported = 0;
        $updated = 0;
        DB::transaction(function () use ($validItems, $itemsToUpdate, &$imported, &$updated) {
            foreach (array_chunk($validItems, 500) as $chunk) {
                PromotionItem::insert($chunk);
                $imported += count($chunk);
            }

            foreach ($itemsToUpdate as $itemUpdate) {
                $data = $itemUpdate['data'];
                unset($data['created_at']);
                PromotionItem::where('id', $itemUpdate['id'])->update($data);
                $updated++;
            }
        });

        return [
            'total_read' => $totalRead,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Map header names to normalized internal keys.
     */
    protected function buildHeaderMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $header) {
            $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$header));

            if (str_contains($normalized, 'branch')) {
                $map['branch_name'] = $index;
            } elseif (str_contains($normalized, 'barcode')) {
                $map['barcode'] = $index;
            } elseif (str_contains($normalized, 'productdesignsubgroup') || str_contains($normalized, 'subgroup')) {
                $map['product_subgroup_name'] = $index;
            } elseif ($normalized === 'product' || str_contains($normalized, 'productname')) {
                $map['product'] = $index;
            } elseif (str_contains($normalized, 'goldweight')) {
                $map['gold_weight'] = $index;
            } elseif (str_contains($normalized, 'dgweight')) {
                $map['dg_weight'] = $index;
            } elseif (str_contains($normalized, 'additionwastage')) {
                $map['addition_wastage'] = $index;
            } elseif (str_contains($normalized, 'totalwastage')) {
                $map['total_wastage'] = $index;
            } elseif (str_contains($normalized, 'weight')) {
                $map['weight'] = $index;
            } elseif (str_contains($normalized, 'laborfee')) {
                $map['labor_fee'] = $index;
            } elseif (str_contains($normalized, 'internalreference')) {
                $map['internal_reference'] = $index;
            } elseif (str_contains($normalized, 'lotserial') || str_contains($normalized, 'lot') || str_contains($normalized, 'serial')) {
                $map['lot_serial_number'] = $index;
            } elseif (str_contains($normalized, 'category')) {
                $map['product_category'] = $index;
            }
        }

        return $map;
    }

    /**
     * Map cell values according to detected headers.
     */
    protected function mapRow(array $rowValues, array $headerMap): array
    {
        $data = [
            'barcode' => '',
            'product' => '',
            'weight' => '',
            'gold_weight' => '',
            'dg_weight' => '',
            'addition_wastage' => '',
            'total_wastage' => '',
            'labor_fee' => '',
            'internal_reference' => '',
            'lot_serial_number' => '',
            'product_subgroup_name' => '',
            'branch_name' => '',
            'product_category' => '',
        ];

        foreach ($headerMap as $key => $index) {
            $data[$key] = $rowValues[$index] ?? '';
        }

        return $data;
    }

    /**
     * Determine if a row is a grouping or summary banner.
     * E.g., 'B1/Promotion (3)' where product and lot/serial number are empty.
     */
    protected function isGroupingOrSummaryRow(array $rowData): bool
    {
        $barcode = $rowData['barcode'] ?? '';
        $product = $rowData['product'] ?? '';
        $lotSerial = $rowData['lot_serial_number'] ?? '';

        if (preg_match('/promotion\s*\(\d+\)/i', $barcode)) {
            return true;
        }

        if (empty($product) && empty($lotSerial)) {
            return true;
        }

        return false;
    }

    /**
     * Safely parse decimal numbers.
     */
    protected function parseDecimal(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        if ($cleaned === '' || !is_numeric($cleaned)) {
            return null;
        }

        return $cleaned;
    }
}
