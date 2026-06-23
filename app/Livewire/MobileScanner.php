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
use Filament\Notifications\Notification;
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

    public array $cameraAttachments = [];

    public array $scanConfigFields = [];

    public ?array $lastResult = null;

    public ?string $flashMessage = null;

    public ?string $flashTone = null;

    public ?string $comment = null;

    public bool $showDuplicateWarning = false;

    public array $duplicateChecks = [];

    public bool $showSelectionModal = false;

    public bool $showScannerModal = false;

    public bool $showMatchedModal = false;

    public function mount(): void
    {
        // dd(auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Supervisor', 'Checker']));
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['super-admin', 'admin', 'manager', 'checker', 'Super Admin', 'Admin', 'Supervisor', 'Checker']), 403);

        $this->checkSessionId = CheckSession::whereIn('status', ['DRAFT', 'OPEN'])->value('id');
    }

    public function updatedProductTypeId(): void
    {
        $product = $this->matchedProductId ? Product::find($this->matchedProductId) : null;
        if (! $product || $product->product_type_id != $this->productTypeId) {
            $this->matchedProductId = null;
            $this->actualValues = [];
        }

        $this->scanConfigId = null;
        $this->scanConfigFields = [];
    }

    public function updatedScanConfigId(): void
    {
        $this->refreshConfigFields();

        if (! empty($this->scanConfigFields)) {
            $allowedFields = collect($this->scanConfigFields)
                ->pluck('field')
                ->filter()
                ->values()
                ->all();

            $this->actualValues = array_intersect_key($this->actualValues, array_flip($allowedFields));
        } else {
            $this->actualValues = [];
        }

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

    public function save(bool $confirmDuplicate = false): void
    {
        if (! $this->checkSessionId || ! $this->productTypeId || ! $this->scanConfigId) {
            $this->showSelectionModal = true;
            $this->showMatchedModal = false;
            $this->showScannerModal = false;

            return;
        }

        // Resolve product from the scanned code WITHOUT triggering prefill/presentation changes
        $code = trim($this->scanCode);
        $product = null;
        if ($code !== '') {
            $product = Product::with('attributeValues')
                ->where('code', $code)
                ->orWhere('barcode', $code)
                ->orWhere('qr_code', $code)
                ->first();

            $this->matchedProductId = $product?->id;
        }

        // If $product wasn't resolved from code above, fall back to matchedProductId
        if (! $product) {
            $product = $this->matchedProductId
                ? Product::with('attributeValues')->find($this->matchedProductId)
                : null;
        }

        if (!$confirmDuplicate) {
            $duplicates = collect();

            if ($product) {
                $duplicates = ProductCheck::with('checkedBy')
                    ->where('check_session_id', $this->checkSessionId)
                    ->where('product_id', $product->id)
                    ->get();
            } else {
                $duplicates = ProductCheck::with('checkedBy')
                    ->where('check_session_id', $this->checkSessionId)
                    ->whereNull('product_id')
                    ->where('remark', 'like', "%({$this->scanCode})%")
                    ->get();
            }

            if ($duplicates->isNotEmpty()) {
                $this->duplicateChecks = $duplicates->toArray();
                $this->showDuplicateWarning = true;
                return;
            }
        }

        $this->refreshConfigFields();

        $validationRules = [
            'checkSessionId' => ['required', 'exists:check_sessions,id'],
            'scanConfigId' => ['required', 'exists:scan_configs,id'],
            'scanCode' => ['required', 'string', 'max:255'],
        ];

        if ($product) {
            $validationRules = array_merge($validationRules, $this->getActualValueValidationRules());
        }

        $this->validate($validationRules);

        $scanConfig = ScanConfig::findOrFail($this->scanConfigId);
        $uploadedAttachments = array_merge($this->attachments, $this->cameraAttachments);

        if ($product) {
            $validation = app(ValidationEngine::class)->validate($scanConfig, $product, $this->actualValues);
        } else {
            $validation = [
                'result_status' => 'WARNING',
                'values' => $this->buildMismatchValues(),
                'errors' => [
                    "No matching product was found for the scanned code ({$this->scanCode}). Saved as a manual mismatch review.",
                ],
            ];
        }

        if (!$product && count($uploadedAttachments) === 0) {
            $this->showDuplicateWarning = false;
            $this->flashMessage = 'Attachment required: please upload at least one photo before saving an unmatched mismatch check.';
            $this->flashTone = 'warning';
            $this->showMatchedModal = true;
            $this->showScannerModal = false;

            Notification::make()
                ->title('Attachment required')
                ->body('Please upload at least one photo before saving an unmatched mismatch check.')
                ->warning()
                ->send();

            return;
        }

        // Add metadata so the view can show who checked, which code and when
        $validation['checked_by_id'] = auth()->id();
        $validation['checked_by_name'] = auth()->user()?->name ?? null;
        $validation['checked_at'] = now()->toDateTimeString();
        $validation['scanned_code'] = $this->scanCode;

        $check = ProductCheck::create([
            'check_session_id' => $this->checkSessionId,
            'scan_config_id' => $this->scanConfigId,
            'product_id' => $product?->id,
            'checked_by' => auth()->id(),
            'checked_at' => now(),
            'result_status' => $validation['result_status'],
            'remark' => $validation['errors'] ? implode(' | ', $validation['errors']) . ($this->comment ? ' | Comment: ' . $this->comment : '') : $this->comment,
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

        foreach ($uploadedAttachments as $attachment) {
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
        $this->scanCode = '';
        $this->matchedProductId = null;
        $this->actualValues = [];
        $this->showMatchedModal = false;
        $this->showScannerModal = false;
        $this->showDuplicateWarning = false;
        $this->duplicateChecks = [];
        $this->comment = null;
        $this->attachments = [];
        $this->cameraAttachments = [];
        $this->dispatch('check-saved');

        $savedNotification = Notification::make()
            ->title($product ? 'Check saved successfully' : 'Mismatch saved with caution')
            ->body($product ? 'The matched product check has been stored.' : 'The scan was saved as a manual mismatch review.');

        if ($product && $validation['result_status'] === 'PASS') {
            $savedNotification->success();
        } else {
            $savedNotification->warning();
        }

        $savedNotification->send();

        $this->flashMessage = null;
        $this->flashTone = null;
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
                    'field_name' => null,
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
        $this->showDuplicateWarning = false;
        $this->duplicateChecks = [];

        if (! $this->checkSessionId || ! $this->productTypeId || ! $this->scanConfigId) {
            $this->showSelectionModal = true;
            $this->showMatchedModal = false;
            $this->showScannerModal = false;

            // Notification::make()
            //     ->title('Complete selection first')
            //     ->body('Please choose the check session, product type, and scan config before saving.')
            //     ->warning()
            //     ->send();

            return;
        }

        $code = trim($this->scanCode);

        if ($code === '') {
            $this->matchedProductId = null;
            $this->showMatchedModal = false;
            return;
        }

        $product = Product::with('attributeValues')
            ->where('code', $code)
            ->orWhere('barcode', $code)
            ->orWhere('qr_code', $code)
            ->first();

        $this->matchedProductId = $product?->id;
        $this->showMatchedModal = true;
        $this->showScannerModal = false;

        if ($product) {
            $this->productTypeId = $product->product_type_id;

            // Auto-select scan config if none is selected, or if the current scan config doesn't match the new product type
            $validConfig = false;
            if ($this->scanConfigId) {
                $currentConfig = ScanConfig::find($this->scanConfigId);
                if ($currentConfig && $currentConfig->product_type_id == $this->productTypeId && $currentConfig->is_active) {
                    $validConfig = true;
                }
            }

            if (! $validConfig) {
                $firstConfig = ScanConfig::where('product_type_id', $this->productTypeId)
                    ->where('is_active', true)
                    ->first();
                if ($firstConfig) {
                    $this->scanConfigId = $firstConfig->id;
                } else {
                    $this->scanConfigId = null;
                }
            }

            $this->refreshConfigFields();
            $this->prefillActualValues();
            $this->flashMessage = 'Product matched successfully.';
            $this->flashTone = 'success';
        } else {
            $this->flashMessage = 'No product matched. Please upload a photo for review or scan again.';
            $this->flashTone = 'warning';
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

    protected function getActualValueValidationRules(): array
    {
        $rules = [];

        foreach ($this->scanConfigFields as $fieldConfig) {
            $fieldName = $fieldConfig['field'] ?? null;
            if (! $fieldName) {
                continue;
            }

            $rules["actualValues.{$fieldName}"] = $fieldConfig['required'] ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function buildMismatchValues(): array
    {
        return collect($this->scanConfigFields)
            ->map(function (array $fieldConfig) {
                $fieldName = $fieldConfig['field'] ?? null;
                $actualValue = $fieldName ? ($this->actualValues[$fieldName] ?? null) : null;

                return [
                    'field_name' => $fieldName ?? 'Field',
                    'expected_value' => null,
                    'actual_value' => $actualValue,
                    'difference_value' => null,
                    'status' => $actualValue ? 'WARNING' : 'FAIL',
                ];
            })
            ->filter(fn(array $value) => $value['field_name'] !== null)
            ->values()
            ->all();
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
