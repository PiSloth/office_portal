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

        $standardHeaders = [
            'code',
            'name',
            'category_name',
            'sub_category_name',
            'location_code',
            'barcode',
            'qr_code',
            'description',
        ];

        $dynamicFields = $productType?->productTypeFields ?? collect();

        $headers = array_merge(
            $standardHeaders,
            $dynamicFields->map(fn (ProductTypeField $field) => strtolower($field->field_name))->all()
        );

        $sampleRow = [
            'PRD-1001',
            'Sample Product',
            'Default Category',
            'General',
            'LOC-001',
            '1234567890123',
            'QR-1001',
            'Imported from the template file',
        ];

        foreach ($dynamicFields as $field) {
            $sampleRow[] = $this->sampleValueForField($field);
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
            'boolean' => '1',
            'textarea', 'select', 'text' => 'Sample ' . Str::headline($field->field_label),
            default => 'Sample Value',
        };
    }
}
