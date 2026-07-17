<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use App\Models\ProductTypeField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductImportTemplateController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'product_type_id' => ['nullable', 'integer', 'exists:product_types,id'],
        ]);

        $productType = ! empty($data['product_type_id'])
            ? ProductType::with([
                'productTypeFields' => fn ($query) => $query->where('is_active', true)->orderBy('id'),
            ])->find($data['product_type_id'])
            : null;

        $standardSamples = [
            'code' => 'PRD-1001',
            'name' => 'Sample Product',
            'category_name' => 'Default Category',
            'sub_category_name' => 'General',
            'location_code' => 'LOC-001',
            'barcode' => '1234567890123',
            'qr_code' => 'QR-1001',
            'quantity' => '100',
            'description' => 'Imported from the template file',
        ];

        $dynamicFields = $productType?->productTypeFields ?? collect();

        $requestedFields = $request->query('fields');
        if (!empty($requestedFields)) {
            $headers = explode(',', $requestedFields);
            $sampleRow = [];
            foreach ($headers as $header) {
                $headerLower = strtolower(trim($header));
                if (array_key_exists($headerLower, $standardSamples)) {
                    $sampleRow[] = $standardSamples[$headerLower];
                } else {
                    $field = $dynamicFields->first(fn($f) => strtolower($f->field_name) === $headerLower);
                    if ($field) {
                        $sampleRow[] = $this->sampleValueForField($field);
                    } else {
                        $sampleRow[] = '';
                    }
                }
            }
        } else {
            $standardHeaders = array_keys($standardSamples);
            $headers = array_merge(
                $standardHeaders,
                $dynamicFields->map(fn (ProductTypeField $field) => strtolower($field->field_name))->all()
            );

            $sampleRow = array_values($standardSamples);
            foreach ($dynamicFields as $field) {
                $sampleRow[] = $this->sampleValueForField($field);
            }
        }

        $fileName = $productType
            ? 'product-import-template-' . Str::slug($productType->name) . '.csv'
            : 'product-import-template.csv';

        return response()->streamDownload(function () use ($headers, $sampleRow): void {
            $output = fopen('php://output', 'w');
            fprintf($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            fputcsv($output, $sampleRow);
            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function sampleValueForField(ProductTypeField $field): string
    {
        return match ($field->field_type) {
            'number' => '100',
            'decimal' => '12.5',
            'date' => now()->toDateString(),
            'boolean' => 'yes',
            'branch_id' => 'Branch A',
            'textarea', 'select', 'text' => 'Sample ' . Str::headline($field->field_label),
            default => 'Sample Value',
        };
    }
}
