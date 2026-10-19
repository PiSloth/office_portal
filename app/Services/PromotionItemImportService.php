<?php

namespace App\Services;

use App\Models\Branch;
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
     * @return array{total_read: int, imported: int, skipped: int}
     * @throws \Exception
     */
    public function import(UploadedFile $file): array
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

        // Preload existing barcodes and lot numbers to check uniqueness against database
        $existingBarcodes = PromotionItem::whereNotNull('barcode')
            ->pluck('barcode')
            ->mapWithKeys(fn ($b) => [trim($b) => true])
            ->toArray();

        $existingLotNumbers = PromotionItem::whereNotNull('lot_serial_number')
            ->pluck('lot_serial_number')
            ->mapWithKeys(fn ($l) => [trim($l) => true])
            ->toArray();

        // Track items encountered within the current file being imported
        $fileBarcodes = [];
        $fileLotNumbers = [];

        $rowErrors = [];
        $validItems = [];

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

                // Validate barcode uniqueness (must not be empty, must be unique in DB and within file)
                if ($barcode !== '') {
                    if (isset($existingBarcodes[$barcode])) {
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'barcode' => $barcode,
                            'product' => $rowData['product'],
                            'error' => "Duplicate barcode '{$barcode}' already exists in database (Barcode စနစ်ထဲတွင် ရှိပြီးသားဖြစ်နေပါသည်).",
                        ];
                        $hasRowError = true;
                    } elseif (isset($fileBarcodes[$barcode])) {
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

                // Validate lot/serial number uniqueness (must not be empty, must be unique in DB and within file)
                if ($lotSerialNumber !== '') {
                    if (isset($existingLotNumbers[$lotSerialNumber])) {
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'barcode' => $barcode,
                            'product' => $rowData['product'],
                            'error' => "Duplicate Lot/Serial '{$lotSerialNumber}' already exists in database (Lot/Serial နံပါတ် စနစ်ထဲတွင် ရှိပြီးသားဖြစ်နေပါသည်).",
                        ];
                        $hasRowError = true;
                    } elseif (isset($fileLotNumbers[$lotSerialNumber])) {
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

                if (!$hasRowError) {
                    $validItems[] = [
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
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Only process the first sheet
            break;
        }

        $reader->close();

        // If there are validation errors (duplicate barcode/lot or branch mismatch), abort the import and return error details
        if (!empty($rowErrors)) {
            $errorCount = count($rowErrors);
            throw new \App\Exceptions\PromotionImportValidationException($rowErrors);
        }

        // Insert all valid items in chunks
        $imported = 0;
        DB::transaction(function () use ($validItems, &$imported) {
            foreach (array_chunk($validItems, 500) as $chunk) {
                PromotionItem::insert($chunk);
                $imported += count($chunk);
            }
        });

        return [
            'total_read' => $totalRead,
            'imported' => $imported,
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
