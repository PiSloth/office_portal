<?php

namespace App\Livewire;

use App\Events\ProductChecked;
use App\Models\Attachment;
use App\Models\CheckSession;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductCheck;
use App\Models\ProductCheckValue;
use App\Models\ScanConfig;
use App\Services\ValidationEngine;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MobileScanner extends Component
{
    use WithFileUploads;

    public ?int $checkSessionId = null;

    public ?int $scanConfigId = null;

    public ?int $productTypeId = null;

    public ?int $matchedProductId = null;

    public string $scanCode = '';

    public array $actualValues = [];

    public array $attachments = [];

    public array $scanConfigFields = [];

    public ?array $lastResult = null;

    public ?string $flashMessage = null;

    public ?string $flashTone = null;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Supervisor', 'Checker']), 403);

        $this->checkSessionId = CheckSession::whereIn('status', ['DRAFT', 'OPEN'])->value('id');
    }

    public function updatedProductTypeId(): void
    {
        $this->scanConfigId = null;
        $this->matchedProductId = null;
        $this->actualValues = [];
        $this->scanConfigFields = [];
    }

    public function updatedScanConfigId(): void
    {
        $this->refreshConfigFields();
        $this->prefillActualValues();
    }

    public function updatedScanCode(): void
    {
        $this->matchProductFromCode();
    }

    public function handleScan(string $decodedText): void
    {
        $this->scanCode = trim($decodedText);
        $this->matchProductFromCode();
    }

    public function save(): void
    {
        $this->validate([
            'checkSessionId' => ['required', 'exists:check_sessions,id'],
            'scanConfigId' => ['required', 'exists:scan_configs,id'],
            'scanCode' => ['required', 'string', 'max:255'],
        ]);

        $this->matchProductFromCode();

        abort_unless($this->matchedProductId, 422, 'No product matched the scanned code.');

        $product = Product::with('attributeValues')->findOrFail($this->matchedProductId);
        $scanConfig = ScanConfig::findOrFail($this->scanConfigId);
        $validation = app(ValidationEngine::class)->validate($scanConfig, $product, $this->actualValues);

        $check = ProductCheck::create([
            'check_session_id' => $this->checkSessionId,
            'product_id' => $product->id,
            'checked_by' => auth()->id(),
            'checked_at' => now(),
            'result_status' => $validation['result_status'],
            'remark' => $validation['errors'] ? implode(' | ', $validation['errors']) : null,
        ]);

        foreach ($validation['values'] as $value) {
            ProductCheckValue::create([
                'product_check_id' => $check->id,
                'field_name' => $value['field_name'],
                'expected_value' => $value['expected_value'],
                'actual_value' => $value['actual_value'],
                'difference_value' => $value['difference_value'],
                'status' => $value['status'],
            ]);
        }

        foreach ($this->attachments as $attachment) {
            $storedPath = $attachment->store('attachments/checks', 'public');

            Attachment::create([
                'attachable_type' => ProductCheck::class,
                'attachable_id' => $check->id,
                'file_path' => $storedPath,
                'file_name' => $attachment->getClientOriginalName(),
                'file_type' => $attachment->getMimeType() ?: 'application/octet-stream',
                'file_size' => $attachment->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        event(new ProductChecked($check));

        $this->lastResult = $validation;
        $this->flashMessage = 'Check saved successfully.';
        $this->flashTone = $validation['result_status'] === 'PASS' ? 'success' : 'warning';
    }

    protected function refreshConfigFields(): void
    {
        $this->scanConfigFields = [];

        if (! $this->scanConfigId) {
            return;
        }

        $scanConfig = ScanConfig::find($this->scanConfigId);
        $this->scanConfigFields = collect(data_get($scanConfig?->config_json, 'fields', []))
            ->map(function (array $fieldConfig): array {
                return array_merge([
                    'field' => null,
                    'source' => 'product',
                    'required' => false,
                    'compare' => false,
                    'tolerance' => null,
                ], $fieldConfig);
            })
            ->values()
            ->all();
    }

    public function matchProductFromCode(): void
    {
        $code = trim($this->scanCode);

        if ($code === '') {
            $this->matchedProductId = null;
            return;
        }

        $product = Product::with('attributeValues')
            ->where('code', $code)
            ->orWhere('barcode', $code)
            ->orWhere('qr_code', $code)
            ->first();

        $this->matchedProductId = $product?->id;

        if ($product) {
            $this->prefillActualValues();
        }
    }

    protected function prefillActualValues(): void
    {
        $product = $this->matchedProductId
            ? Product::with('attributeValues')->find($this->matchedProductId)
            : null;

        if (! $product) {
            return;
        }

        foreach ($this->scanConfigFields as $fieldConfig) {
            $fieldName = $fieldConfig['field'] ?? null;

            if (! $fieldName || ($fieldConfig['source'] ?? 'product') !== 'product') {
                continue;
            }

            $this->actualValues[$fieldName] = $this->resolveExpectedValue($product, $fieldName);
        }
    }

    protected function resolveExpectedValue(Product $product, string $fieldName): mixed
    {
        return match ($fieldName) {
            'location_id', 'category_id', 'sub_category_id' => $product->{$fieldName},
            'code', 'barcode', 'qr_code', 'name', 'description', 'status' => $product->{$fieldName},
            default => $product->attributeValues->firstWhere('field_name', $fieldName)?->value,
        };
    }

    public function render()
    {
        return view('livewire.mobile-scanner', [
            'sessions' => CheckSession::latest('started_at')->get(),
            'productTypes' => \App\Models\ProductType::where('is_active', true)->orderBy('name')->get(),
            'scanConfigs' => $this->productTypeId
                ? ScanConfig::where('product_type_id', $this->productTypeId)->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'selectedProduct' => $this->matchedProductId
                ? Product::with(['productType', 'location', 'category', 'subCategory', 'attributeValues'])->find($this->matchedProductId)
                : null,
            'selectedScanConfig' => $this->scanConfigId ? ScanConfig::find($this->scanConfigId) : null,
            'scanStats' => [
                'open_sessions' => CheckSession::where('status', 'OPEN')->count(),
                'active_configs' => ScanConfig::where('is_active', true)->count(),
            ],
        ])->layout('layouts.app');
    }
}
