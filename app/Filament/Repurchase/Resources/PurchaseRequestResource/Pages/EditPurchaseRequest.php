<?php

namespace App\Filament\Repurchase\Resources\PurchaseRequestResource\Pages;

use App\Filament\Repurchase\Resources\PurchaseRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Text;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        $actions = [];

        // 2. Dynamic Workflow Transitions
        $transitions = \App\Modules\Core\Workflow\Models\WorkflowTransition::where('from_state_id', $record->workflow_state_id)->get();
        foreach ($transitions as $transition) {
            $hasRules = false;
            $steps = [];
            $rules = collect();
            $startStepIndex = 1;

            // Load Checklist items if configured
            $hasChecklist = false;
            $checklistItems = collect();
            if ($transition->checklist_id) {
                $checklist = \App\Modules\Core\Workflow\Models\Checklist::find($transition->checklist_id);
                if ($checklist) {
                    $checklistItems = $checklist->items()->where('is_active', true)->orderBy('sort_order', 'asc')->get();
                    if ($checklistItems->isNotEmpty()) {
                        $hasChecklist = true;
                    }
                }
            }

            if ($transition->validation_rule_set_id) {
                $ruleSet = \App\Modules\Core\Validation\Models\ValidationRuleSet::find($transition->validation_rule_set_id);
                if ($ruleSet && $ruleSet->rules->isNotEmpty() && $record->items->isNotEmpty()) {
                    $hasRules = false;
                    $rules = $ruleSet->rules;

                    $stepIndex = 1;
                    $foundNextUnchecked = false;
                    $activeRulesCount = 0;
                    foreach ($record->items as $itemIndex => $item) {
                        foreach ($rules as $ruleIndex => $rule) {
                            $validationManager = new \App\Modules\Core\Validation\Services\ValidationManager();
                            $expectedVal = $validationManager->resolveExpectedValue($rule->expected_source ?: $rule->field_name, $item);

                            // Skip if is_skip_zero is true and expected value is 0
                            if ($rule->is_skip_zero && ($expectedVal === 0 || $expectedVal === 0.0 || $expectedVal === '0' || $expectedVal === '0.00')) {
                                continue;
                            }

                            // Skip if is_based_grade is true and grade does not match
                            if ($rule->is_based_grade) {
                                $itemGrade = $item->dynamic_fields_json['goldList'] ?? null;
                                $allowedGrades = $rule->grades_json ?? [];
                                if (!in_array((string)$itemGrade, array_map('strval', $allowedGrades), true)) {
                                    continue;
                                }
                            }

                            $activeRulesCount++;
                            $hasRules = true;

                            // Check if this user already has a validation history for this item and this rule
                            $hasHistory = \App\Modules\Core\Validation\Models\ValidationHistory::where('validatable_type', get_class($item))
                                ->where('validatable_id', $item->id)
                                ->where('rule_id', $rule->id)
                                ->where('user_id', auth()->id())
                                ->exists();

                            if (!$hasHistory && !$foundNextUnchecked) {
                                $startStepIndex = $stepIndex;
                                $foundNextUnchecked = true;
                            }

                            $existingHistory = \App\Modules\Core\Validation\Models\ValidationHistory::where('validatable_type', get_class($item))
                                ->where('validatable_id', $item->id)
                                ->where('rule_id', $rule->id)
                                ->where('user_id', auth()->id())
                                ->latest()
                                ->first();

                            $stepSchema = [
                                \Filament\Forms\Components\Placeholder::make("info_{$item->id}_{$rule->id}")
                                    ->label("Product Info")
                                    ->content(($item->dynamic_fields_json['product_name'] ?? 'Product')),
                                \Filament\Forms\Components\Placeholder::make("expected_info_{$item->id}_{$rule->id}")
                                    ->label("Expected " . ($rule->label ?: $rule->field_name))
                                    ->content(new \Illuminate\Support\HtmlString(
                                        '<span style="background-color: #fef08a; color: #854d0e; padding: 4px 12px; border-radius: 9999px; font-weight: bold; font-size: 1.1rem; border: 1px solid #fde047; display: inline-block;">' . 
                                        e($expectedVal ?? 'Any') . 
                                        '</span>'
                                    )),
                            ];

                            // If rule is required, user must input the related value and system evaluates PASS/FAIL.
                            // If not required, verifier manually selects Correct/Fail.
                            if ($rule->is_required) {
                                $fieldType = 'text';
                                $fieldName = $rule->field_name;

                                $numericFields = ['kyat', 'pae', 'yawe', 'kyaukWeight', 'goldWeightGram', 'percent', 'quantity', 'total_amount'];
                                $booleanFields = ['is_good'];

                                if (in_array($fieldName, $numericFields, true)) {
                                    $fieldType = 'number';
                                } elseif (in_array($fieldName, $booleanFields, true)) {
                                    $fieldType = 'boolean';
                                } else {
                                    $productTypeId = $record->product_type_id;
                                    if ($productTypeId) {
                                        $dbField = \App\Models\ProductTypeField::query()
                                            ->where('product_type_id', $productTypeId)
                                            ->where('field_name', $fieldName)
                                            ->first();
                                        if ($dbField && $dbField->field_type) {
                                            $type = strtolower($dbField->field_type);
                                            if (in_array($type, ['number', 'numeric', 'integer', 'float', 'decimal'])) {
                                                $fieldType = 'number';
                                            } elseif (in_array($type, ['boolean', 'toggle', 'switch', 'checkbox'])) {
                                                $fieldType = 'boolean';
                                            }
                                        }
                                    }
                                }

                                if ($fieldType === 'number') {
                                    $stepSchema[] = \Filament\Forms\Components\TextInput::make("verify_input_{$item->id}_{$rule->id}")
                                        ->label("Enter Actual " . ($rule->label ?: $rule->field_name))
                                        ->numeric()
                                        ->default($existingHistory?->input_value)
                                        ->required();
                                } elseif ($fieldType === 'boolean') {
                                    $stepSchema[] = \Filament\Forms\Components\Radio::make("verify_input_{$item->id}_{$rule->id}")
                                        ->label($rule->label ?: $rule->field_name)
                                        ->options([
                                            '1' => 'ရ',
                                            '0' => 'မရ',
                                        ])
                                        ->inline()
                                        ->default(blank($existingHistory?->input_value) ? '0' : (string)intval(filter_var($existingHistory?->input_value, FILTER_VALIDATE_BOOLEAN)))
                                        ->required();
                                } else {
                                    $stepSchema[] = \Filament\Forms\Components\TextInput::make("verify_input_{$item->id}_{$rule->id}")
                                        ->label("Enter Actual " . ($rule->label ?: $rule->field_name))
                                        ->default($existingHistory?->input_value)
                                        ->required();
                                }
                            } else {
                                $stepSchema[] = \Filament\Forms\Components\Radio::make("verify_{$item->id}_{$rule->id}")
                                    ->label("Verification Status")
                                    ->options([
                                        'PASS' => 'Correct (Pass)',
                                        'FAIL' => 'Fail',
                                    ])
                                    ->default($existingHistory?->status)
                                    ->required();
                            }

                            $stepSchema[] = \Filament\Forms\Components\Textarea::make("remarks_{$item->id}_{$rule->id}")
                                ->label("Checker Remark")
                                ->default($existingHistory?->remarks)
                                ->rows(2);

                            $stepSchema[] = \Filament\Schemas\Components\Actions::make([
                                \Filament\Actions\Action::make('reset_verify')
                                    ->label('Reset All Checks')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->action(function () use ($record) {
                                        \App\Modules\Core\Validation\Models\ValidationHistory::whereIn(
                                            'validatable_id',
                                            $record->items->pluck('id')->toArray()
                                        )
                                            ->where('validatable_type', \App\Modules\Purchase\Models\PurchaseItem::class)
                                            ->delete();

                                        \Filament\Notifications\Notification::make()
                                            ->title("Verification Reset")
                                            ->body("All verification histories for this request have been cleared.")
                                            ->success()
                                            ->send();

                                        $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                                    })
                            ]);

                            $steps[] = \Filament\Schemas\Components\Wizard\Step::make("step_{$item->id}_{$rule->id}")
                                ->label("Item #" . ($itemIndex + 1) . ": " . ($rule->label ?? $rule->field_name))
                                ->schema($stepSchema)
                                ->afterValidation(function (\Filament\Schemas\Components\Utilities\Get $get) use ($item, $rule) {
                                    $validationManager = new \App\Modules\Core\Validation\Services\ValidationManager();
                                    $expectedValue = $validationManager->resolveExpectedValue($rule->expected_source ?: $rule->field_name, $item);
                                    $remarks = $get("remarks_{$item->id}_{$rule->id}");

                                    if ($rule->is_required) {
                                        $inputValue = $get("verify_input_{$item->id}_{$rule->id}");
                                        $status = $validationManager->evaluate($inputValue, $expectedValue, $rule->operator, $rule->tolerance) ? 'PASS' : 'FAIL';
                                    } else {
                                        $inputValue = null;
                                        $status = $get("verify_{$item->id}_{$rule->id}") ?? 'FAIL';
                                    }

                                    if ($status || $remarks) {
                                        \App\Modules\Core\Validation\Models\ValidationHistory::updateOrCreate([
                                            'validatable_type' => get_class($item),
                                            'validatable_id' => $item->id,
                                            'rule_id' => $rule->id,
                                            'user_id' => auth()->id(),
                                        ], [
                                            'status' => $status,
                                            'input_value' => $inputValue,
                                            'expected_value' => $expectedValue,
                                            'remarks' => $remarks,
                                        ]);
                                    }
                                });

                            $stepIndex++;
                        }
                    }
                }
            }

            // Append Checklist Step if hasRules and hasChecklist
            if ($hasRules && $hasChecklist) {
                $checklistStepSchema = [];
                foreach ($checklistItems as $checkItem) {
                    $existingChecked = \App\Modules\Purchase\Models\PurchaseRequestChecklist::where('purchase_request_id', $record->id)
                        ->where('checklist_item_id', $checkItem->id)
                        ->value('is_checked') ?? false;

                    $checklistStepSchema[] = \Filament\Forms\Components\Checkbox::make("checklist_item_{$checkItem->id}")
                        ->label($checkItem->label)
                        ->default($existingChecked)
                        ->required();
                }
                
                $steps[] = \Filament\Schemas\Components\Wizard\Step::make("step_checklist")
                    ->label("Verification Checklist")
                    ->schema($checklistStepSchema);
            }

            $action = Actions\Action::make('transition_' . $transition->id)
                ->label($transition->action_name)
                ->color($transition->action_name === 'Reject' ? 'danger' : 'success');

            if ($hasRules) {
                $action->steps($steps)
                    ->startOnStep($startStepIndex)
                    ->action(function (array $data) use ($record, $transition, $rules, $hasChecklist, $checklistItems) {
                        $allPassed = true;
                        $validationManager = new \App\Modules\Core\Validation\Services\ValidationManager();

                        foreach ($record->items as $item) {
                            foreach ($rules as $rule) {
                                $expectedValue = $validationManager->resolveExpectedValue($rule->expected_source ?: $rule->field_name, $item);

                                // Skip if is_skip_zero is true and expected value is 0
                                if ($rule->is_skip_zero && ($expectedValue === 0 || $expectedValue === 0.0 || $expectedValue === '0' || $expectedValue === '0.00')) {
                                    continue;
                                }

                                // Skip if is_based_grade is true and grade does not match
                                if ($rule->is_based_grade) {
                                    $itemGrade = $item->dynamic_fields_json['goldList'] ?? null;
                                    $allowedGrades = $rule->grades_json ?? [];
                                    if (!in_array((string)$itemGrade, array_map('strval', $allowedGrades), true)) {
                                        continue;
                                    }
                                }

                                $remarks = $data["remarks_{$item->id}_{$rule->id}"] ?? null;

                                if ($rule->is_required) {
                                    $inputValue = $data["verify_input_{$item->id}_{$rule->id}"] ?? null;
                                    $status = $validationManager->evaluate($inputValue, $expectedValue, $rule->operator, $rule->tolerance) ? 'PASS' : 'FAIL';
                                } else {
                                    $inputValue = null;
                                    $status = $data["verify_{$item->id}_{$rule->id}"] ?? 'FAIL';
                                }

                                if ($status === 'FAIL') {
                                    $allPassed = false;
                                }

                                \App\Modules\Core\Validation\Models\ValidationHistory::updateOrCreate([
                                    'validatable_type' => get_class($item),
                                    'validatable_id' => $item->id,
                                    'rule_id' => $rule->id,
                                    'user_id' => auth()->id(),
                                ], [
                                    'status' => $status,
                                    'input_value' => $inputValue,
                                    'expected_value' => $expectedValue,
                                    'remarks' => $remarks,
                                ]);
                            }
                        }

                        // Save Checklist States
                        if ($hasChecklist) {
                            foreach ($checklistItems as $checkItem) {
                                $isChecked = (bool) ($data["checklist_item_{$checkItem->id}"] ?? false);
                                \App\Modules\Purchase\Models\PurchaseRequestChecklist::updateOrCreate([
                                    'purchase_request_id' => $record->id,
                                    'checklist_item_id' => $checkItem->id,
                                ], [
                                    'is_checked' => $isChecked,
                                    'user_id' => auth()->id(),
                                ]);
                            }
                        }

                        if ($allPassed) {
                            $record->workflow_state_id = $transition->to_state_id;
                            $record->status_updated_by_id = auth()->id();
                            $record->save();

                            \Filament\Notifications\Notification::make()
                                ->title("Request transitioned to {$record->workflowState->name}")
                                ->success()
                                ->send();

                            $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title("Failed to transition request")
                                ->body("One or more validation rules were marked as FAIL. Transition blocked.")
                                ->danger()
                                ->send();

                            $this->record->refresh();
                            $this->fillForm();
                        }
                    });
            } else {
                // No validation rules but might have Checklist
                if ($hasChecklist) {
                    $formSchema = [];
                    foreach ($checklistItems as $checkItem) {
                        $existingChecked = \App\Modules\Purchase\Models\PurchaseRequestChecklist::where('purchase_request_id', $record->id)
                            ->where('checklist_item_id', $checkItem->id)
                            ->value('is_checked') ?? false;

                        $formSchema[] = \Filament\Forms\Components\Checkbox::make("checklist_item_{$checkItem->id}")
                            ->label($checkItem->label)
                            ->default($existingChecked)
                            ->required();
                    }
                    
                    $action->form($formSchema)
                        ->action(function (array $data) use ($record, $transition, $checklistItems) {
                            // Save Checklist States
                            foreach ($checklistItems as $checkItem) {
                                $isChecked = (bool) ($data["checklist_item_{$checkItem->id}"] ?? false);
                                \App\Modules\Purchase\Models\PurchaseRequestChecklist::updateOrCreate([
                                    'purchase_request_id' => $record->id,
                                    'checklist_item_id' => $checkItem->id,
                                ], [
                                    'is_checked' => $isChecked,
                                    'user_id' => auth()->id(),
                                ]);
                            }

                            $record->status_updated_by_id = auth()->id();
                            $manager = new \App\Modules\Core\Workflow\Services\WorkflowManager();
                            if ($manager->transition($record, $transition, auth()->user())) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Request transitioned to {$record->workflowState->name}")
                                    ->success()
                                    ->send();

                                $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title("Failed to transition request")
                                    ->body("Validation rules may have failed or you do not have permission.")
                                    ->danger()
                                    ->send();
                            }
                        });
                } else {
                    $action->requiresConfirmation()
                        ->action(function () use ($record, $transition) {
                            $record->status_updated_by_id = auth()->id();
                            $manager = new \App\Modules\Core\Workflow\Services\WorkflowManager();
                            if ($manager->transition($record, $transition, auth()->user())) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Request transitioned to {$record->workflowState->name}")
                                    ->success()
                                    ->send();

                                $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title("Failed to transition request")
                                    ->body("Validation rules may have failed or you do not have permission.")
                                    ->danger()
                                    ->send();
                            }
                        });
                }
            }

            $actions[] = $action;
        }

        // 2.5 Standalone Print Voucher Action
        $actions[] = Actions\Action::make('print_voucher')
            ->label('Print Voucher')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(fn() => route('purchase-requests.print', ['record' => $this->record]))
            ->openUrlInNewTab();

        // 3. Settings Dropdown Button Group (Settings icon only) containing print history and delete
        $actions[] = Actions\ActionGroup::make([
            Actions\Action::make('view_overall_checklist')
            ->label('Workflow Checklist')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('gray')
            ->modalHeading(fn() => "Workflow Checklist for Request {$this->record->purchase_number}")
            ->modalWidth('4xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->form(function () use ($record) {
                return [
                    \Filament\Forms\Components\Placeholder::make('overall_checklist')
                        ->hiddenLabel()
                        ->content(function () use ($record) {
                            $productTypeId = $record->product_type_id;
                            $workflow = \App\Modules\Core\Workflow\Models\Workflow::where('product_type_id', $productTypeId)
                                ->where('is_active', true)
                                ->first();
                            if (!$workflow) {
                                $workflow = \App\Modules\Core\Workflow\Models\Workflow::first();
                            }
                            
                            if (!$workflow) {
                                return 'No workflow configured.';
                            }
                            
                            $transitions = $workflow->transitions()->with('checklist.items', 'fromState', 'toState')->get();
                            
                            $html = '<div class="overflow-x-auto"><table class="w-full text-left border-collapse text-sm">';
                            $html .= '<thead><tr class="border-b border-gray-200 dark:border-gray-800 text-gray-400 font-semibold">';
                            $html .= '<th class="py-2 pr-4">Workflow Stage</th>';
                            $html .= '<th class="py-2 px-4">Checklist Task</th>';
                            $html .= '<th class="py-2 px-4">Status</th>';
                            $html .= '<th class="py-2 px-4">Checked By</th>';
                            $html .= '<th class="py-2 px-4">Checked At</th>';
                            $html .= '</tr></thead><tbody>';
                            
                            $hasChecklist = false;
                            
                            foreach ($transitions as $transition) {
                                if (!$transition->checklist || $transition->checklist->items->where('is_active', true)->isEmpty()) {
                                    continue;
                                }
                                
                                $hasChecklist = true;
                                $stageName = e($transition->action_name) . ' (' . e($transition->fromState->name) . ' ➔ ' . e($transition->toState->name) . ')';
                                
                                foreach ($transition->checklist->items as $item) {
                                    if (!$item->is_active) continue;
                                    
                                    $checkedRecord = \App\Modules\Purchase\Models\PurchaseRequestChecklist::where('purchase_request_id', $record->id)
                                        ->where('checklist_item_id', $item->id)
                                        ->first();
                                        
                                    $isChecked = $checkedRecord?->is_checked ?? false;
                                    
                                    $statusIcon = $isChecked 
                                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-400">✓ Completed</span>'
                                        : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">✗ Pending</span>';
                                        
                                    $textClass = $isChecked 
                                        ? 'text-gray-800 dark:text-gray-200 line-through decoration-success-500/50' 
                                        : 'text-gray-750 dark:text-gray-300';
                                        
                                    $requiredLabel = $item->is_required 
                                        ? ' <span class="text-[10px] text-danger-500 font-semibold">(Required)</span>' 
                                        : '';
                                        
                                    $checkedBy = $isChecked && $checkedRecord?->user ? e($checkedRecord->user->name) : '-';
                                    $checkedAt = $isChecked && $checkedRecord?->updated_at ? $checkedRecord->updated_at->format('d M Y, h:i A') : '-';

                                    $html .= '<tr class="border-b border-gray-100 dark:border-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50">';
                                    $html .= '<td class="py-3 pr-4 font-semibold text-gray-600 dark:text-gray-400">' . $stageName . '</td>';
                                    $html .= '<td class="py-3 px-4 ' . $textClass . '">' . e($item->label) . $requiredLabel . '</td>';
                                    $html .= '<td class="py-3 px-4">' . $statusIcon . '</td>';
                                    $html .= '<td class="py-3 px-4">' . $checkedBy . '</td>';
                                    $html .= '<td class="py-3 px-4 text-xs text-gray-500">' . $checkedAt . '</td>';
                                    $html .= '</tr>';
                                }
                            }
                            
                            if (!$hasChecklist) {
                                return new \Illuminate\Support\HtmlString('<div class="text-center py-4 text-gray-500">No checklists configured for this request workflow.</div>');
                            }
                            
                            $html .= '</tbody></table></div>';
                            return new \Illuminate\Support\HtmlString($html);
                        })
                ];
            }),

            // 1. History Action
        Actions\Action::make('view_history')
            ->label('History')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->modalHeading(fn() => "Calculation History for Request #{$this->record->id}")
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->form(function () {
                return [
                    \Filament\Forms\Components\Placeholder::make('history_log')
                        ->hiddenLabel()
                        ->content(function () {
                            $histories = \App\Modules\Core\Calculation\Models\CalculationHistory::whereHasMorph(
                                'calculatable',
                                [\App\Modules\Purchase\Models\PurchaseItem::class],
                                fn($query) => $query->where('purchase_request_id', $this->record->id)
                            )->with('user', 'calculatable.productType')->latest()->get();

                            if ($histories->isEmpty()) {
                                return new \Illuminate\Support\HtmlString('<div class="text-center py-4 text-gray-500">No calculation history recorded yet.</div>');
                            }

                            $html = '<div class="overflow-x-auto"><table class="w-full text-left border-collapse text-sm">';
                            $html .= '<thead><tr class="border-b border-gray-200 dark:border-gray-800 text-gray-400 font-semibold">';
                            $html .= '<th class="py-2 pr-4">Date/Time</th>';
                            $html .= '<th class="py-2 px-4">Operator</th>';
                            $html .= '<th class="py-2 px-4">Product Name</th>';
                            $html .= '<th class="py-2 px-4">Weight</th>';
                            $html .= '<th class="py-2 px-4">Gold Grade</th>';
                            $html .= '<th class="py-2 px-4">Qty</th>';
                            $html .= '<th class="py-2 px-4">အလဲအထပ်</th>';
                            $html .= '<th class="py-2 px-4">ရ/မရ</th>';
                            $html .= '<th class="py-2 px-4">Deduction</th>';
                            $html .= '<th class="py-2 px-4">Price</th>';
                            $html .= '<th class="py-2 px-4">Remark</th>';
                            $html .= '</tr></thead><tbody>';

                            foreach ($histories as $history) {
                                $inputs = $history->input_snapshot_json ?? [];
                                $productName = $inputs['product_name'] ?? '-';
                                $goldGrade = ($inputs['goldList'] ?? '-') . ' ပဲ';
                                $weight = ($inputs['goldWeightGram'] ?? '0') . ' g';
                                if (!empty($inputs['kyat']) || !empty($inputs['pae']) || !empty($inputs['yawe'])) {
                                    $weight = ($inputs['kyat'] ?? 0) . 'ကျပ် ' . ($inputs['pae'] ?? 0) . 'ပဲ ' . ($inputs['yawe'] ?? 0) . 'ရွေး (' . ($inputs['goldWeightGram'] ?? '0') . ' g)';
                                }
                                $date = $history->created_at->format('d M Y, h:i A');
                                $operator = $history->user?->name ?? 'System';
                                $price = number_format($history->total_amount) . ' MMK';
                                $qty = $inputs['quantity'] ?? 1;
                                $reChange = ($inputs['reChange'] ?? '0') === '1' ? 'အလဲအထပ် (Yes)' : 'ဆိုင်ထည် (No)';
                                $isGood = ($inputs['is_good'] ?? false) ? 'ရ' : 'မရ';
                                $deduction = ($inputs['percent'] ?? 0) . '%';
                                $remark = $inputs['remark'] ?? '-';

                                $html .= '<tr class="border-b border-gray-100 dark:border-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50">';
                                $html .= '<td class="py-3 pr-4 font-medium">' . e($date) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($operator) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($productName) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($weight) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($goldGrade) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($qty) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($reChange) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($isGood) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($deduction) . '</td>';
                                $html .= '<td class="py-3 px-4 font-semibold text-success-600">' . e($price) . '</td>';
                                $html .= '<td class="py-3 px-4 text-gray-500">' . e($remark) . '</td>';
                                $html .= '</tr>';
                            }

                            $html .= '</tbody></table></div>';

                            return new \Illuminate\Support\HtmlString($html);
                        })
                ];
            }),

            Actions\Action::make('view_print_history')
                ->label('Print Logs')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('gray')
                ->modalHeading(fn() => "Print History for Request {$this->record->purchase_number}")
                ->modalWidth('3xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->form(function () {
                    return [
                        \Filament\Forms\Components\Placeholder::make('print_logs')
                            ->hiddenLabel()
                            ->content(function () {
                                $logs = \App\Modules\Purchase\Models\PurchaseRequestPrintLog::where('purchase_request_id', $this->record->id)
                                    ->with('user')
                                    ->latest()
                                    ->get();

                                if ($logs->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString('<div class="text-center py-4 text-gray-500">No print logs recorded yet.</div>');
                                }

                                $html = '<div class="overflow-x-auto"><table class="w-full text-left border-collapse text-sm">';
                                $html .= '<thead><tr class="border-b border-gray-200 dark:border-gray-800 text-gray-400 font-semibold">';
                                $html .= '<th class="py-2 pr-4">Printed At</th>';
                                $html .= '<th class="py-2 px-4">Printed By</th>';
                                $html .= '</tr></thead><tbody>';

                                foreach ($logs as $log) {
                                    $date = $log->printed_at->format('d M Y, h:i A');
                                    $printedBy = $log->user?->name ?? 'System';

                                    $html .= '<tr class="border-b border-gray-100 dark:border-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50">';
                                    $html .= '<td class="py-3 pr-4 font-medium">' . e($date) . '</td>';
                                    $html .= '<td class="py-3 px-4">' . e($printedBy) . '</td>';
                                    $html .= '</tr>';
                                }

                                $html .= '</tbody></table></div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                    ];
                }),

            Actions\DeleteAction::make()
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->can('purchase-requests.delete') ?? false),
        ])
        ->icon('heroicon-m-cog-6-tooth')
        ->color('gray')
        ->button();

        return $actions;
    }

    protected function beforeSave(): void
    {
        if (empty($this->data['items'])) {
            \Filament\Notifications\Notification::make()
                ->title('Validation Error')
                ->body("We can't create when no purchase items are prepared.")
                ->danger()
                ->send();

            $this->halt();
        }

        // Log customer info changes if print logs exist
        $hasPrintLogs = \App\Modules\Purchase\Models\PurchaseRequestPrintLog::where('purchase_request_id', $this->record->id)->exists();
        if ($hasPrintLogs) {
            $original = $this->record->getOriginal();
            $current = $this->data;

            $changes = [];
            if (($original['customer_name'] ?? '') !== ($current['customer_name'] ?? '')) {
                $changes['customer_name'] = [
                    'old' => $original['customer_name'] ?? '',
                    'new' => $current['customer_name'] ?? '',
                ];
            }
            if (($original['customer_phone'] ?? '') !== ($current['customer_phone'] ?? '')) {
                $changes['customer_phone'] = [
                    'old' => $original['customer_phone'] ?? '',
                    'new' => $current['customer_phone'] ?? '',
                ];
            }
            if (($original['customer_address'] ?? '') !== ($current['customer_address'] ?? '')) {
                $changes['customer_address'] = [
                    'old' => $original['customer_address'] ?? '',
                    'new' => $current['customer_address'] ?? '',
                ];
            }

            if (!empty($changes)) {
                $logFile = storage_path('logs/customer_changes_log.json');
                $logData = [];
                if (file_exists($logFile)) {
                    $logData = json_decode(file_get_contents($logFile), true) ?? [];
                }
                $logData[] = [
                    'purchase_request_id' => $this->record->id,
                    'purchase_number' => $this->record->purchase_number,
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()?->name,
                    'timestamp' => now()->toDateTimeString(),
                    'changes' => $changes,
                ];
                if (!is_dir(dirname($logFile))) {
                    mkdir(dirname($logFile), 0777, true);
                }
                file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
