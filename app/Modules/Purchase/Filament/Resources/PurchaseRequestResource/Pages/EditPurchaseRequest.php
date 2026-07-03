<?php

namespace App\Modules\Purchase\Filament\Resources\PurchaseRequestResource\Pages;

use App\Modules\Purchase\Filament\Resources\PurchaseRequestResource;
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

        // 1. History Action
        $actions[] = Actions\Action::make('view_history')
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
                            $html .= '<th class="py-2 px-4">Gold Grade</th>';
                            $html .= '<th class="py-2 px-4">Weight</th>';
                            $html .= '<th class="py-2 px-4">Price</th>';
                            $html .= '</tr></thead><tbody>';

                            foreach ($histories as $history) {
                                $inputs = $history->input_snapshot_json ?? [];
                                $productName = $inputs['product_name'] ?? '-';
                                $goldGrade = ($inputs['goldList'] ?? '-') . ' ပဲ';
                                $weight = ($inputs['goldWeightGram'] ?? '0') . ' g';
                                if (!empty($inputs['kyat']) || !empty($inputs['pae']) || !empty($inputs['yawe'])) {
                                    $weight = ($inputs['kyat'] ?? 0) . 'ကျပ် ' . ($inputs['pae'] ?? 0) . 'ပဲ ' . ($inputs['yawe'] ?? 0) . 'ရွေး';
                                }
                                $date = $history->created_at->format('d M Y, h:i A');
                                $operator = $history->user?->name ?? 'System';
                                $price = number_format($history->total_amount) . ' MMK';

                                $html .= '<tr class="border-b border-gray-100 dark:border-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50">';
                                $html .= '<td class="py-3 pr-4 font-medium">' . e($date) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($operator) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($productName) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($goldGrade) . '</td>';
                                $html .= '<td class="py-3 px-4">' . e($weight) . '</td>';
                                $html .= '<td class="py-3 px-4 font-semibold text-success-600">' . e($price) . '</td>';
                                $html .= '</tr>';
                            }

                            $html .= '</tbody></table></div>';

                            return new \Illuminate\Support\HtmlString($html);
                        })
                ];
            });

        // 2. Dynamic Workflow Transitions
        $transitions = \App\Modules\Core\Workflow\Models\WorkflowTransition::where('from_state_id', $record->workflow_state_id)->get();
        foreach ($transitions as $transition) {
            $hasRules = false;
            $steps = [];
            $rules = collect();
            $startStepIndex = 1;

            if ($transition->validation_rule_set_id) {
                $ruleSet = \App\Modules\Core\Validation\Models\ValidationRuleSet::find($transition->validation_rule_set_id);
                if ($ruleSet && $ruleSet->rules->isNotEmpty() && $record->items->isNotEmpty()) {
                    $hasRules = true;
                    $rules = $ruleSet->rules;

                    $stepIndex = 1;
                    $foundNextUnchecked = false;
                    foreach ($record->items as $itemIndex => $item) {
                        foreach ($rules as $ruleIndex => $rule) {
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

                            $validationManager = new \App\Modules\Core\Validation\Services\ValidationManager();
                            $expectedVal = $validationManager->resolveExpectedValue($rule->expected_source ?: $rule->field_name, $item);

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
                                $stepSchema[] = \Filament\Forms\Components\TextInput::make("verify_input_{$item->id}_{$rule->id}")
                                    ->label("Enter Actual Value")
                                    ->default($existingHistory?->input_value)
                                    ->required();
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

            $action = Actions\Action::make('transition_' . $transition->id)
                ->label($transition->action_name)
                ->color($transition->action_name === 'Reject' ? 'danger' : 'success');

            if ($hasRules) {
                $action->steps($steps)
                    ->startOnStep($startStepIndex)
                    ->action(function (array $data) use ($record, $transition, $rules) {
                        $allPassed = true;
                        $validationManager = new \App\Modules\Core\Validation\Services\ValidationManager();

                        foreach ($record->items as $item) {
                            foreach ($rules as $rule) {
                                $remarks = $data["remarks_{$item->id}_{$rule->id}"] ?? null;
                                $expectedValue = $validationManager->resolveExpectedValue($rule->expected_source ?: $rule->field_name, $item);

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

            $actions[] = $action;
        }

        // 2.5 Print Voucher Action
        $actions[] = Actions\Action::make('print_voucher')
            ->label('Print Voucher')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(fn() => route('purchase-requests.print', ['record' => $this->record]))
            ->openUrlInNewTab();

        // 2.7 Print History Action
        $actions[] = Actions\Action::make('view_print_history')
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
            });

        // 3. Delete Action
        $actions[] = Actions\DeleteAction::make();

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
