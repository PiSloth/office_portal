<?php

namespace App\Livewire;

use App\Events\ProductChecked;
use App\Models\Attachment;
use App\Models\CheckSession;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductCheck;
use App\Models\ProductCheckValue;
use App\Models\Location;
use App\Models\DecisionType;
use App\Models\Decision;
use App\Models\Comment;
use App\Models\ScanConfig;
use App\Models\ProductType;
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

    public ?int $selectedLocationId = null;
    public ?string $newLocationName = null;
    public ?string $newLocationDescription = null;
    public bool $showCreateLocationModal = false;
    
    public ?int $activeCheckId = null;
    public ?int $deletedCheckIdToRestore = null;
    
    // For create unmatched product
    public bool $showCreateProductModal = false;
    public string $createProductName = '';
    public string $createProductCode = '';
    public ?int $createProductTypeId = null;
    public ?int $createProductLocationId = null;
    public ?int $createProductCategoryId = null;
    public ?int $createProductSubCategoryId = null;
    public string $createProductBarcode = '';
    public string $createProductQrCode = '';
    public int $createProductQuantity = 1;
    public string $createProductStatus = 'ACTIVE';
    public array $categoriesList = [];
    public array $subCategoriesList = [];
    public array $createProductDynamicFields = [];
    public array $createProductDynamicValues = [];
    
    // For remark / decision
    public bool $showRemarkModal = false;
    public ?string $remarkText = null;
    public ?int $decisionTypeId = null;

    public ?int $matchedProductId = null;
    public string $scanCode = '';
    public array $actualValues = [];
    public array $attachments = [];
    public array $cameraAttachments = [];
    public array $scanConfigFields = [];
    public ?array $lastResult = null;
    public ?string $flashMessage = null;
    public ?string $flashTone = null;
    public string $flashId = '';
    public ?string $comment = null;
    public bool $showDuplicateWarning = false;
    public array $duplicateChecks = [];
    public bool $showScannerModal = false;
    public bool $showMatchedModal = false;
    public array $productTypeDynamicFields = [];

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['super-admin', 'admin', 'manager', 'checker', 'Super Admin', 'Admin', 'Supervisor', 'Checker']), 403);
        $session = CheckSession::where(function ($query) {
                $query->where('assigned_user_id', auth()->id())
                    ->orWhereHas('assignedUsers', function ($q) {
                        $q->where('users.id', auth()->id());
                    });
            })
            ->whereIn('status', ['DRAFT', 'OPEN'])
            ->latest('started_at')
            ->first();

        if (!$session) {
            $session = CheckSession::whereIn('status', ['DRAFT', 'OPEN'])->latest('started_at')->first();
        }
        $this->checkSessionId = $session?->id;
        $this->productTypeId = $session?->product_type_id;
        $this->scanConfigId = $session?->scan_config_id;
        $this->refreshConfigFields();
        $this->loadProductTypeDynamicFields();
        
        $defaultLocation = Location::where('name', 'Default Location')->first();
        if (!$defaultLocation) {
            $defaultLocation = Location::create([
                'code' => 'DEF-LOC',
                'name' => 'Default Location',
                'description' => 'System default location'
            ]);
        }
        $this->selectedLocationId = session('scanner_selected_location_id', $defaultLocation->id);
    }

    public function updatedSelectedLocationId($value): void
    {
        session(['scanner_selected_location_id' => $value]);
    }

    public function createLocation()
    {
        $this->validate([
            'newLocationName' => 'required|string|max:255',
            'newLocationDescription' => 'nullable|string'
        ]);

        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->newLocationName), 0, 8)) . '-' . rand(100, 999);
        $loc = Location::create([
            'code' => $code,
            'name' => $this->newLocationName,
            'description' => $this->newLocationDescription
        ]);
        $this->selectedLocationId = $loc->id;
        session(['scanner_selected_location_id' => $loc->id]);
        $this->showCreateLocationModal = false;
        $this->newLocationName = null;
        $this->newLocationDescription = null;
    }

    public function updatedProductTypeId(): void
    {
        $this->scanConfigId = null;
        $this->scanConfigFields = [];
        $this->loadProductTypeDynamicFields();
    }

    public function updatedScanConfigId(): void
    {
        $this->refreshConfigFields();
    }

    protected function hasPendingChecks(): bool
    {
        if (!$this->checkSessionId) {
            return false;
        }

        return \App\Models\ProductCheck::where('check_session_id', $this->checkSessionId)
            ->where('checked_by', auth()->id())
            ->where('result_status', 'PENDING')
            ->exists();
    }

    protected function hasUnmatchedChecks(): bool
    {
        if (!$this->checkSessionId) {
            return false;
        }

        return \App\Models\ProductCheck::where('check_session_id', $this->checkSessionId)
            ->where('checked_by', auth()->id())
            ->whereNull('product_id')
            ->exists();
    }

    protected function flash(string $message, string $tone = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashTone = $tone;
        $this->flashId = uniqid();
    }

    public function handleScan(string $decodedText): void
    {
        $this->scanCode = trim($decodedText);
        
        if (! $this->checkSessionId) {
            $this->flash('No active session selected. Please open a session first.', 'warning');
            return;
        }

        if ($this->hasPendingChecks()) {
            $this->flash('Please complete the pending check validation before scanning another product.', 'warning');
            $this->scanCode = '';
            return;
        }

        if ($this->hasUnmatchedChecks()) {
            $this->flash('Unmatched check found. Please create the product before scanning another item.', 'warning');
            $this->scanCode = '';
            return;
        }

        $code = $this->scanCode;
        if ($code === '') return;

        $product = Product::with('attributeValues')
            ->where('code', $code)
            ->orWhere('barcode', $code)
            ->orWhere('qr_code', $code)
            ->first();

        // Check if already checked in this session by this user
        $existingCheck = ProductCheck::where('check_session_id', $this->checkSessionId)
            ->where('barcode', $code)
            ->where('checked_by', auth()->id())
            ->first();

        if ($existingCheck) {
            $existingCheck->increment('quantity');
            $existingCheck->touch();

            if ($this->scanConfigId && collect($this->scanConfigFields)->contains('field', 'quantity')) {
                // If there's a dynamic field called 'quantity', sync the built-in incremented quantity to it
                // This automatically triggers the ValidationEngine through updateInlineCheckValue
                $this->updateInlineCheckValue($existingCheck->id, 'quantity', $existingCheck->quantity);
            } else {
                if ($existingCheck->product) {
                    $this->runFullValidation($existingCheck);
                }
            }

            $this->flash('Quantity updated for existing check.', 'success');
        } else {
            $autoStatus = $this->getAutoStatusForProduct($product);
            $closingStock = $product ? (int) $product->quantity : 0;
            if (1 !== $closingStock && $autoStatus === 'PASS') {
                $autoStatus = 'WARNING';
            }

            $newCheck = ProductCheck::create([
                'check_session_id' => $this->checkSessionId,
                'scan_config_id' => $this->scanConfigId,
                'product_id' => $product?->id,
                'barcode' => $code,
                'quantity' => 1,
                'location_id' => $this->selectedLocationId,
                'checked_by' => auth()->id(),
                'checked_at' => now(),
                'result_status' => 'PENDING', // Will be resolved right after
            ]);
            
            if ($product) {
                $newCheck->update(['result_status' => $this->resolveCheckStatus($newCheck, $autoStatus)]);
                $this->flash('Scanned successfully.', 'success');
            } else {
                $newCheck->update(['result_status' => 'UNMATCHED']);
                event(new \App\Events\ProductChecked($newCheck));
                $this->openCreateProduct($newCheck->id);
                $this->flash('Unmatched product scanned. Please create the product.', 'warning');
            }

            if ($this->scanConfigId && collect($this->scanConfigFields)->contains('field', 'quantity')) {
                $this->updateInlineCheckValue($newCheck->id, 'quantity', 1);
            }
        }
        
        $this->scanCode = '';
        $this->showScannerModal = false;
    }

    public function updateCheckQuantity($checkId, $quantity)
    {
        $check = ProductCheck::with(['product.attributeValues', 'checkValues'])->find($checkId);
        if ($check && is_numeric($quantity) && $quantity > 0) {
            $check->quantity = (int)$quantity;
            $check->save();
            $this->runFullValidation($check);
        }
    }

    public function updateProductName($productId, $name)
    {
        $product = Product::find($productId);
        if ($product && trim($name) !== '') {
            $product->update(['name' => trim($name)]);
        }
    }

    public function processScannedCode(): void
    {
        $code = trim($this->scanCode);
        if ($code === '') {
            $this->flash('Please enter or scan a code before launching.', 'warning');
            return;
        }

        $this->handleScan($code);
    }

    public function deleteCheck(int $checkId): void
    {
        $check = ProductCheck::find($checkId);
        if (! $check) {
            $this->flash('Check not found.', 'warning');
            return;
        }

        $check->delete();
        $this->deletedCheckIdToRestore = $check->id;
        $this->flash('Check deleted.', 'warning');
    }

    public function restoreCheck(int $checkId): void
    {
        $check = ProductCheck::withTrashed()->find($checkId);
        if (! $check || ! $check->trashed()) {
            $this->flash('Nothing to restore.', 'warning');
            return;
        }

        $check->restore();
        $this->deletedCheckIdToRestore = null;
        $this->flash('Check restored successfully.', 'success');
    }

    public function openCreateProduct($checkId)
    {
        $this->activeCheckId = $checkId;
        $check = ProductCheck::find($checkId);
        $this->createProductCode = $check->barcode ?? '';
        $this->createProductBarcode = $check->barcode ?? '';
        $this->createProductQrCode = '';
        $this->createProductName = '';
        $this->createProductQuantity = 1;
        $this->createProductStatus = 'ACTIVE';
        $this->createProductLocationId = $check->location_id ?? $this->selectedLocationId;
        $this->createProductTypeId = $this->productTypeId;
        
        $this->updatedCreateProductTypeId();
        $this->showCreateProductModal = true;
    }

    public function updatedCreateProductTypeId()
    {
        $this->createProductCategoryId = null;
        $this->createProductSubCategoryId = null;
        $this->categoriesList = [];
        $this->subCategoriesList = [];
        $this->createProductDynamicFields = [];
        $this->createProductDynamicValues = [];
        
        if ($this->createProductTypeId) {
            $this->categoriesList = \App\Models\Category::where('product_type_id', $this->createProductTypeId)
                ->pluck('name', 'id')
                ->toArray();

            $fields = \App\Models\ProductTypeField::where('product_type_id', $this->createProductTypeId)
                ->where('is_active', true)
                ->where('show_in_creation_form', true)
                ->get();
            
            foreach ($fields as $field) {
                $this->createProductDynamicFields[] = $field->toArray();
                if (($field->field_type ?? '') === 'branch_id') {
                    $this->createProductDynamicValues[$field->field_name] = (string)(auth()->user()->branch_id ?? '');
                } else {
                    $this->createProductDynamicValues[$field->field_name] = '';
                }
            }
        }
    }

    public function updatedCreateProductCategoryId()
    {
        $this->createProductSubCategoryId = null;
        $this->subCategoriesList = [];
        if ($this->createProductCategoryId) {
            $this->subCategoriesList = \App\Models\SubCategory::where('category_id', $this->createProductCategoryId)
                ->pluck('name', 'id')
                ->toArray();
        }
    }

    public function saveCreatedProduct()
    {
        $rules = [
            'createProductTypeId' => 'required|exists:product_types,id',
            'createProductLocationId' => 'required|exists:locations,id',
            'createProductCategoryId' => 'required|exists:categories,id',
            'createProductSubCategoryId' => 'nullable|exists:sub_categories,id',
            'createProductCode' => 'required|string|unique:products,code',
            'createProductBarcode' => 'nullable|string',
            'createProductQrCode' => 'nullable|string',
            'createProductName' => 'required|string',
            'createProductQuantity' => 'required|integer|min:0',
            'createProductStatus' => 'required|in:ACTIVE,SUSPENDED',
        ];

        foreach ($this->createProductDynamicFields as $field) {
            if (($field['field_type'] ?? '') === 'branch_id') {
                $rules['createProductDynamicValues.' . $field['field_name']] = 'nullable';
            } else if (isset($field['required']) && $field['required']) {
                $rules['createProductDynamicValues.' . $field['field_name']] = 'required';
            } else {
                $rules['createProductDynamicValues.' . $field['field_name']] = 'nullable';
            }
        }

        foreach ($this->createProductDynamicFields as $field) {
            if (($field['field_type'] ?? '') === 'branch_id') {
                $this->createProductDynamicValues[$field['field_name']] = (string)(auth()->user()->branch_id ?? '');
            }
        }

        $this->validate($rules);
        
        $product = Product::create([
            'product_type_id' => $this->createProductTypeId,
            'location_id' => $this->createProductLocationId,
            'category_id' => $this->createProductCategoryId,
            'sub_category_id' => $this->createProductSubCategoryId,
            'code' => $this->createProductCode,
            'barcode' => $this->createProductBarcode,
            'qr_code' => $this->createProductQrCode,
            'name' => $this->createProductName,
            'quantity' => $this->createProductQuantity,
            'status' => $this->createProductStatus,
            'created_during_pickup' => true
        ]);

        foreach ($this->createProductDynamicValues as $fieldName => $value) {
            if ($value !== null && $value !== '') {
                ProductAttributeValue::create([
                    'product_id' => $product->id,
                    'field_name' => $fieldName,
                    'value' => $value
                ]);
            }
        }

        if ($this->activeCheckId) {
            $check = ProductCheck::find($this->activeCheckId);
            if ($check) {
                $check->update([
                    'product_id' => $product->id,
                    'result_status' => 'UNMATCHED',
                ]);
                $this->runFullValidation($check);
            }
        }

        $this->showCreateProductModal = false;
        $this->activeCheckId = null;
        $this->flash('Product created and linked.', 'success');
    }

    public function openComparison($checkId)
    {
        $this->activeCheckId = $checkId;
        $check = ProductCheck::with('product.attributeValues')->find($checkId);
        
        if (! $check || ! $check->product) {
            $this->flash('Cannot validate unmatched product. Create product first.', 'warning');
            return;
        }

        $this->matchedProductId = $check->product_id;
        $this->productTypeId = $check->product->product_type_id;

        if (! $this->scanConfigId) {
            $firstConfig = ScanConfig::where('product_type_id', $this->productTypeId)
                ->where('is_active', true)
                ->first();
            $this->scanConfigId = $firstConfig?->id;
        }
        
        $this->refreshConfigFields();
        $this->prefillActualValues();
        $this->showMatchedModal = true;
    }

    public function saveValidation()
    {
        if (! $this->activeCheckId || ! $this->scanConfigId) return;

        $check = ProductCheck::with('product')->find($this->activeCheckId);
        if (! $check || ! $check->product) return;

        $this->validate($this->getActualValueValidationRules());

        $scanConfig = ScanConfig::findOrFail($this->scanConfigId);
        $uploadedAttachments = array_merge($this->attachments, $this->cameraAttachments);

        $this->runFullValidation($check, $this->actualValues);

        if ($this->comment) {
            $check->remark = $check->remark ? $check->remark . ' | Comment: ' . $this->comment : 'Comment: ' . $this->comment;
            $check->save();
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

        $this->showMatchedModal = false;
        $this->activeCheckId = null;
        $this->attachments = [];
        $this->cameraAttachments = [];
        $this->actualValues = [];
        $this->comment = null;

        $this->flash('Check validation saved.', $validation['result_status'] === 'PASS' ? 'success' : 'warning');
    }

    public function openRemarkModal($checkId)
    {
        $this->activeCheckId = $checkId;
        $this->remarkText = '';
        $this->decisionTypeId = null;
        $this->showRemarkModal = true;
    }

    public function saveRemark()
    {
        if (! $this->activeCheckId) return;

        $check = ProductCheck::find($this->activeCheckId);
        if ($check) {
            $decisionId = null;
            if ($this->decisionTypeId) {
                $decision = Decision::create([
                    'product_check_id' => $check->id,
                    'decision_type_id' => $this->decisionTypeId,
                    'action_status' => 'OPEN',
                    'decision_by' => auth()->id(),
                ]);
                $decisionId = $decision->id;
            }

            if ($this->remarkText) {
                if ($decisionId) {
                    Comment::create([
                        'decision_id' => $decisionId,
                        'user_id' => auth()->id(),
                        'comment_type' => 'DECISION',
                        'comment' => $this->remarkText
                    ]);
                } else {
                    // Update check remark if no decision
                    $check->update([
                        'remark' => $check->remark ? $check->remark . ' | ' . $this->remarkText : $this->remarkText
                    ]);
                }
            }
        }

        $this->showRemarkModal = false;
        $this->activeCheckId = null;
        $this->remarkText = null;
        $this->decisionTypeId = null;
        
        $this->flash('Remark/Decision added.', 'success');
    }

    protected function refreshConfigFields(): void
    {
        $this->scanConfigFields = [];
        if (! $this->scanConfigId) return;

        $scanConfig = ScanConfig::find($this->scanConfigId);
        $dynamicFields = \App\Models\ProductTypeField::where('product_type_id', $this->productTypeId)->get();
        $this->scanConfigFields = collect(data_get($scanConfig?->config_json, 'fields', []))
            ->map(function (array $fieldConfig) use ($dynamicFields): array {
                $fModel = $dynamicFields->firstWhere('field_name', $fieldConfig['field']);
                return array_merge([
                    'field' => null,
                    'field_name' => null,
                    'source' => 'product',
                    'required' => false,
                    'compare' => false,
                    'tolerance' => null,
                    'is_editable_in_table' => false,
                    'is_apply_validate' => false,
                    'is_quickcheck' => false,
                    'field_type' => $fModel?->field_type ?? 'text',
                ], $fieldConfig);
            })->values()->all();
    }

    public function quickCheck(string $fieldName): void
    {
        $product = $this->matchedProductId ? Product::with('attributeValues')->find($this->matchedProductId) : null;
        if (!$product) return;

        $expectedValue = match ($fieldName) {
            'location_id', 'category_id', 'sub_category_id' => $product->{$fieldName},
            'code', 'barcode', 'qr_code', 'name', 'description', 'status' => $product->{$fieldName},
            default => $product->attributeValues->firstWhere('field_name', $fieldName)?->value,
        };

        $this->actualValues[$fieldName] = ($expectedValue !== null) ? (string) $expectedValue : '';
    }

    protected function loadProductTypeDynamicFields(): void
    {
        $this->productTypeDynamicFields = [];
        if ($this->productTypeId) {
            $this->productTypeDynamicFields = \App\Models\ProductTypeField::where('product_type_id', $this->productTypeId)
                ->where('is_active', true)
                ->get()
                ->toArray();
        }
    }

    public function updateInlineCheckValue($checkId, $fieldName, $value)
    {
        $check = ProductCheck::with(['product.attributeValues', 'checkValues'])->find($checkId);
        if (! $check || ! $check->product) return;

        if ($fieldName === 'quantity' && is_numeric($value)) {
            $check->quantity = (int)$value;
            $check->save();
        }

        $this->runFullValidation($check, [$fieldName => $value]);
    }

    protected function prefillActualValues(): void
    {
        $product = $this->matchedProductId ? Product::with('attributeValues')->find($this->matchedProductId) : null;
        if (! $product) return;

        $dynamicFields = \App\Models\ProductTypeField::where('product_type_id', $product->product_type_id)->get();

        foreach ($this->scanConfigFields as $fieldConfig) {
            $fieldName = $fieldConfig['field'] ?? null;
            if (! $fieldName || ($fieldConfig['source'] ?? 'product') !== 'product') continue;

            $fModel = $dynamicFields->firstWhere('field_name', $fieldName);
            if ($fModel && $fModel->field_type === 'branch_id') {
                $this->actualValues[$fieldName] = (string)(auth()->user()->branch_id ?? '');
            } else {
                $this->actualValues[$fieldName] = match ($fieldName) {
                    'location_id', 'category_id', 'sub_category_id' => $product->{$fieldName},
                    'code', 'barcode', 'qr_code', 'name', 'description', 'status' => $product->{$fieldName},
                    default => $product->attributeValues->firstWhere('field_name', $fieldName)?->value,
                };
            }
        }
    }

    protected function getActualValueValidationRules(): array
    {
        $rules = [];
        foreach ($this->scanConfigFields as $fieldConfig) {
            $fieldName = $fieldConfig['field'] ?? null;
            if ($fieldName) {
                $rules["actualValues.{$fieldName}"] = $fieldConfig['required'] ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'];
            }
        }
        return $rules;
    }

    protected function getAutoStatusForProduct(?Product $product): string
    {
        if (!$product) {
            return 'UNMATCHED';
        }

        $scanConfig = ScanConfig::where('product_type_id', $product->product_type_id)
            ->where('is_active', true)
            ->first();

        if (!$scanConfig) {
            return 'PASS';
        }

        $fields = data_get($scanConfig->config_json, 'fields', []);
        $hasCompareFields = collect($fields)->contains(function ($field) {
            return data_get($field, 'compare', false) == true;
        });

        return $hasCompareFields ? 'PENDING' : 'PASS';
    }

    protected function resolveCheckStatus(ProductCheck $check, ?string $baseStatus = 'PASS'): string
    {
        if (!$check->product) {
            return 'UNMATCHED';
        }

        if ($check->product->created_during_pickup) {
            return 'UNMATCHED';
        }

        if ($baseStatus === 'FAIL') {
            return 'FAIL';
        }

        $closingStock = (int) $check->product->quantity;
        
        $otherUsersQty = \App\Models\ProductCheck::where('check_session_id', $check->check_session_id)
            ->where('product_id', $check->product_id)
            ->where('id', '!=', $check->id)
            ->sum('quantity');

        if (((int) $check->quantity + (int) $otherUsersQty) !== $closingStock) {
            return 'FAIL'; // Quantity mismatch overrides PASS/PENDING to FAIL
        }

        return $baseStatus;
    }

    protected function runFullValidation(ProductCheck $check, array $inlineOverrides = []): void
    {
        if (! $check->product) return;
        
        $scanConfig = $this->scanConfigId ? ScanConfig::find($this->scanConfigId) : null;
        
        $baseStatus = 'PASS';
        $errors = [];
        
        if ($scanConfig) {
            $actualValues = [];
            $dynamicFields = \App\Models\ProductTypeField::where('product_type_id', $check->product->product_type_id)->get();
            foreach (data_get($scanConfig->config_json, 'fields', []) as $fieldConfig) {
                $fName = $fieldConfig['field'] ?? null;
                if (! $fName || ($fieldConfig['source'] ?? 'product') !== 'product') continue;
                
                $fModel = $dynamicFields->firstWhere('field_name', $fName);
                if ($fModel && $fModel->field_type === 'branch_id') {
                    $actualValues[$fName] = (string)(auth()->user()->branch_id ?? '');
                    continue;
                }
                
                if ($check->product->created_during_pickup) {
                    if ($fName === 'quantity') {
                        $actualValues[$fName] = $check->quantity;
                    } else {
                        $expectedVal = match ($fName) {
                            'location_id', 'category_id', 'sub_category_id' => $check->product->{$fName},
                            'code', 'barcode', 'qr_code', 'name', 'description', 'status' => $check->product->{$fName},
                            default => $check->product->attributeValues->firstWhere('field_name', $fName)?->value,
                        };
                        $actualValues[$fName] = $expectedVal;
                    }
                } else {
                    if (!data_get($fieldConfig, 'compare', false)) {
                        $actualValues[$fName] = match ($fName) {
                            'location_id', 'category_id', 'sub_category_id' => $check->product->{$fName},
                            'code', 'barcode', 'qr_code', 'name', 'description', 'status', 'quantity' => $check->product->{$fName},
                            default => $check->product->attributeValues->firstWhere('field_name', $fName)?->value,
                        };
                    } else {
                        if ($fName === 'quantity') {
                            $actualValues[$fName] = $check->quantity;
                        } else {
                            $actualValues[$fName] = null;
                        }
                    }
                }
            }
            
            $existingValues = ProductCheckValue::where('product_check_id', $check->id)->get();
            foreach ($existingValues as $ev) {
                if ($ev->field_name !== 'quantity') {
                    $actualValues[$ev->field_name] = $ev->actual_value;
                }
            }
            
            foreach ($inlineOverrides as $k => $v) {
                $actualValues[$k] = $v;
            }

            // Sync updated values to Product if product was created during pickup
            if ($check->product->created_during_pickup) {
                if (isset($actualValues['quantity'])) {
                    $check->quantity = (int) $actualValues['quantity'];
                    $check->save();
                }
                foreach ($actualValues as $fieldName => $value) {
                    if (in_array($fieldName, ['code', 'barcode', 'qr_code', 'name', 'status', 'quantity', 'location_id', 'category_id', 'sub_category_id'])) {
                        $check->product->update([$fieldName => $value]);
                    } else {
                        \App\Models\ProductAttributeValue::updateOrCreate(
                            ['product_id' => $check->product_id, 'field_name' => $fieldName],
                            ['value' => $value]
                        );
                    }
                }
            }
            
            $validation = app(ValidationEngine::class)->validate($scanConfig, $check->product, $actualValues);
            $baseStatus = $validation['result_status'];
            $errors = $validation['errors'] ?? [];

            // If any compare field has not been filled/scanned, force the overall check status to remain PENDING
            $hasEmptyCompareFields = false;
            foreach (data_get($scanConfig->config_json, 'fields', []) as $fieldConfig) {
                if (data_get($fieldConfig, 'compare', false)) {
                    $fName = $fieldConfig['field'] ?? null;
                    if ($fName && ($actualValues[$fName] === null || $actualValues[$fName] === '')) {
                        $hasEmptyCompareFields = true;
                        break;
                    }
                }
            }
            if ($hasEmptyCompareFields) {
                $baseStatus = 'PENDING';
            }
            
            ProductCheckValue::where('product_check_id', $check->id)->delete();
            foreach ($validation['values'] as $val) {
                ProductCheckValue::create([
                    'product_check_id' => $check->id,
                    'field_name' => $val['field_name'],
                    'expected_value' => $val['expected_value'],
                    'actual_value' => $val['actual_value'],
                    'difference_value' => $val['difference_value'],
                    'status' => $val['status'],
                ]);
            }
        }
        
        $check->result_status = $this->resolveCheckStatus($check, $baseStatus);
        $check->remark = $errors ? implode(' | ', $errors) : null;
        if ($scanConfig) {
            $check->scan_config_id = $scanConfig->id;
        }
        $check->save();
        
        event(new ProductChecked($check));
    }

    public function render()
    {
        $recentChecks = ProductCheck::with(['product.attributeValues', 'location', 'decisions.decisionType', 'checkValues'])
            ->where('check_session_id', $this->checkSessionId)
            ->where('checked_by', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();
            
        // Calculate record_qty for each check (total checked by other users for the same product in same session)
        foreach ($recentChecks as $check) {
            if ($check->product_id) {
                $check->record_qty = ProductCheck::where('check_session_id', $this->checkSessionId)
                    ->where('product_id', $check->product_id)
                    ->where('id', '!=', $check->id) // exclude current
                    ->sum('quantity');
                    
            } else {
                $check->record_qty = 0;
            }
        }

        $recentChecksGrouped = $recentChecks->groupBy(function($item) {
            return $item->location ? $item->location->name : 'Unknown Location';
        });

        $selectedLocation = Location::find($this->selectedLocationId);
        $locationScannedCount = ProductCheck::where('check_session_id', $this->checkSessionId)
            ->where('checked_by', auth()->id())
            ->where('location_id', $this->selectedLocationId)
            ->count();
        $locationScannedQty = (int) ProductCheck::where('check_session_id', $this->checkSessionId)
            ->where('checked_by', auth()->id())
            ->where('location_id', $this->selectedLocationId)
            ->sum('quantity');

        return view('livewire.mobile-scanner', [
            'sessions' => CheckSession::latest('started_at')->get(),
            'locations' => Location::orderBy('name')->get(),
            'productTypes' => ProductType::where('is_active', true)->orderBy('name')->get(),
            'decisionTypes' => DecisionType::where('is_active', true)->orderBy('name')->get(),
            'recentChecksGrouped' => $recentChecksGrouped,
            'selectedLocationName' => $selectedLocation?->name ?? 'None',
            'locationScannedCount' => $locationScannedCount,
            'locationScannedQty' => $locationScannedQty,
            'scanConfigs' => $this->productTypeId
                ? ScanConfig::where('product_type_id', $this->productTypeId)->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'selectedProduct' => $this->matchedProductId
                ? Product::with(['productType', 'location', 'category', 'subCategory', 'attributeValues'])->find($this->matchedProductId)
                : null,
            'selectedScanConfig' => $this->scanConfigId ? ScanConfig::find($this->scanConfigId) : null,
        ])->layout('layouts.app');
    }
}
