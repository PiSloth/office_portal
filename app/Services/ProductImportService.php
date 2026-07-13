<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductImportBatch;
use App\Models\ProductImportLog;
use App\Models\ProductType;
use App\Models\ProductTypeField;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImportService
{
    /**
     * Parse and import a product batch.
     *
     * @param ProductImportBatch $batch
     * @return void
     */
    public function import(ProductImportBatch $batch): void
    {
        $filePath = Storage::disk('local')->path($batch->file_path);
        
        if (!file_exists($filePath)) {
            $batch->update([
                'status' => 'FAILED',
            ]);
            ProductImportLog::create([
                'batch_id' => $batch->id,
                'row_number' => 0,
                'data_json' => [],
                'errors_json' => ['file' => 'Import file does not exist on disk.'],
                'status' => 'FAILED',
            ]);
            return;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $batch->update(['status' => 'FAILED']);
            return;
        }

        // Get active dynamic fields for this product type
        $dynamicFields = ProductTypeField::where('product_type_id', $batch->product_type_id)
            ->where('is_active', true)
            ->get();
        $dynamicFieldNames = $dynamicFields->pluck('field_name')->toArray();

        // Parse header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            $batch->update(['status' => 'FAILED']);
            return;
        }

        // Standardize header names
        $headers = array_map(function ($header) {
            return $this->normalizeHeader((string) $header);
        }, $headers);

        $totalRows = 0;
        $importedRows = 0;
        $failedRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $totalRows++;
            $rowData = array_combine(
                array_intersect_key($headers, $row),
                array_intersect_key($row, $headers)
            );

            // pad row if columns count mismatches headers
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = $row[$index] ?? '';
            }

            DB::beginTransaction();
            try {
                $errors = [];

                // 1. Validate Standard Fields
                $code = trim($rowData['code'] ?? '');
                $name = trim($rowData['name'] ?? '');
                $categoryName = trim($rowData['category_name'] ?? '');
                $subCategoryName = trim($rowData['sub_category_name'] ?? '');
                $locationCode = trim($rowData['location_code'] ?? '');
                $barcode = trim($rowData['barcode'] ?? '') ?: null;
                $qrCode = trim($rowData['qr_code'] ?? '') ?: null;
                $quantity = trim($rowData['quantity'] ?? '0');
                $description = trim($rowData['description'] ?? '') ?: null;

                if (!is_numeric($quantity)) {
                    $errors[] = "Quantity must be a numeric value.";
                }

                if (empty($code)) {
                    $errors[] = "Product code is required.";
                }

                if (empty($name)) {
                    $errors[] = "Product name is required.";
                }

                if (empty($categoryName)) {
                    $errors[] = "Category name is required.";
                }

                // 2. Validate Dynamic Fields
                $dynamicValuesToSave = [];
                foreach ($dynamicFields as $field) {
                    $fName = strtolower($field->field_name);
                    $val = trim($rowData[$fName] ?? '');
                    
                    if ($field->required && ($val === null || $val === '')) {
                        $errors[] = "Dynamic field '{$field->field_label}' is required.";
                    }

                    if ($val !== '') {
                        // basic type checks
                        if ($field->field_type === 'number' && !is_numeric($val)) {
                            $errors[] = "Field '{$field->field_label}' must be an integer.";
                        }
                        if ($field->field_type === 'decimal' && !is_numeric($val)) {
                            $errors[] = "Field '{$field->field_label}' must be a decimal number.";
                        }
                        if ($field->field_type === 'date' && !strtotime($val)) {
                            $errors[] = "Field '{$field->field_label}' must be a valid date.";
                        }
                        if ($field->field_type === 'boolean') {
                            $lowerVal = strtolower($val);
                            if ($lowerVal === 'yes' || $lowerVal === 'y' || $lowerVal === 'true' || $lowerVal === '1') {
                                $val = '1';
                            } elseif ($lowerVal === 'no' || $lowerVal === 'n' || $lowerVal === 'false' || $lowerVal === '0') {
                                $val = '0';
                            } else {
                                $errors[] = "Field '{$field->field_label}' must be 'yes' or 'no'.";
                            }
                        }

                        if ($fName === 'weight_g' && is_numeric($val)) {
                            $val = (string) round((float) $val, 2);
                        }

                        $dynamicValuesToSave[$field->field_name] = $val;
                    }
                }

                if (count($errors) > 0) {
                    throw new \Exception(implode(' | ', $errors));
                }

                // 3. Resolve Category
                $category = Category::firstOrCreate([
                    'product_type_id' => $batch->product_type_id,
                    'name' => $categoryName,
                ]);

                // Resolve SubCategory
                $subCategoryId = null;
                if (!empty($subCategoryName)) {
                    $subCategory = SubCategory::firstOrCreate([
                        'category_id' => $category->id,
                        'name' => $subCategoryName,
                    ]);
                    $subCategoryId = $subCategory->id;
                }

                // Resolve Location
                $locationId = null;
                if (!empty($locationCode)) {
                    $location = Location::firstOrCreate(
                        ['code' => $locationCode],
                        ['name' => "Location {$locationCode}"]
                    );
                    $locationId = $location->id;
                }

                // 4. Create or Update Product (Upsert)
                $product = Product::updateOrCreate(
                    ['code' => $code],
                    [
                        'product_type_id' => $batch->product_type_id,
                        'location_id' => $locationId,
                        'category_id' => $category->id,
                        'sub_category_id' => $subCategoryId,
                        'barcode' => $barcode,
                        'qr_code' => $qrCode,
                        'name' => $name,
                        'description' => $description,
                        'quantity' => (int) $quantity,
                        'status' => 'ACTIVE',
                        'import_batch_id' => $batch->id,
                    ]
                );

                // 5. Save/Update Attributes
                foreach ($dynamicValuesToSave as $fName => $val) {
                    ProductAttributeValue::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'field_name' => $fName,
                        ],
                        [
                            'value' => $val,
                        ]
                    );
                }

                // Log Success
                ProductImportLog::create([
                    'batch_id' => $batch->id,
                    'row_number' => $totalRows,
                    'data_json' => $rowData,
                    'errors_json' => null,
                    'status' => 'SUCCESS',
                ]);

                $importedRows++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $failedRows++;
                
                ProductImportLog::create([
                    'batch_id' => $batch->id,
                    'row_number' => $totalRows,
                    'data_json' => $rowData,
                    'errors_json' => ['error' => $e->getMessage()],
                    'status' => 'FAILED',
                ]);
            }
        }

        fclose($handle);

        $batch->update([
            'status' => ($failedRows > 0) ? 'FAILED' : 'SUCCESS',
            'total_rows' => $totalRows,
            'imported_rows' => $importedRows,
            'failed_rows' => $failedRows,
        ]);
    }

    /**
     * Rollback a product import batch by deleting all imported products.
     *
     * @param ProductImportBatch $batch
     * @return void
     */
    public function rollback(ProductImportBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            // Delete products. This will cascade delete product attribute values.
            Product::where('import_batch_id', $batch->id)->delete();

            $batch->update([
                'status' => 'ROLLBACKED',
                'imported_rows' => 0,
            ]);
        });
    }

    /**
     * Normalize CSV header names for matching.
     */
    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return trim(strtolower($header));
    }
}
