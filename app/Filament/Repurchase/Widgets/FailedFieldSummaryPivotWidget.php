<?php

namespace App\Filament\Repurchase\Widgets;

use App\Modules\Core\Workflow\Models\WorkflowState;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class FailedFieldSummaryPivotWidget extends Widget
{
    protected string $view = 'filament.repurchase.widgets.failed-field-summary-pivot-widget';

    protected int | string | array $columnSpan = 'full';

    public ?int $selectedStateId = null;
    public string $workflowFilterMode = 'end_states'; // 'end_states', 'all', 'specific'
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->workflowFilterMode = 'end_states';
    }

    public function getViewData(): array
    {
        $states = WorkflowState::orderBy('name')->get();

        $query = DB::table('fail_checks as fc')
            ->join('purchase_requests as pr', 'fc.purchase_request_id', '=', 'pr.id')
            ->leftJoin('branches as b', 'pr.branch_id', '=', 'b.id')
            ->leftJoin('workflow_states as ws', 'pr.workflow_state_id', '=', 'ws.id')
            ->whereNull('pr.deleted_at');

        // Apply Workflow filter
        if ($this->workflowFilterMode === 'end_states') {
            $query->where('ws.is_end', true);
        } elseif ($this->workflowFilterMode === 'specific' && $this->selectedStateId) {
            $query->where('pr.workflow_state_id', $this->selectedStateId);
        }

        // Apply Date Range filter
        if ($this->startDate) {
            $query->whereDate('fc.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('fc.created_at', '<=', $this->endDate);
        }

        $rows = $query->select([
            'fc.field_name',
            'b.name as branch_name',
            'fc.actual_value',
            'fc.expected_value',
            'fc.purchase_request_id'
        ])->get();

        // Group rows by field name and analyze if the field is numeric
        $groupedByField = [];
        foreach ($rows as $row) {
            $fieldName = $row->field_name ?: 'Unknown Field';
            if (!isset($groupedByField[$fieldName])) {
                $groupedByField[$fieldName] = [];
            }
            $groupedByField[$fieldName][] = $row;
        }

        $reportData = [];
        $booleanReport = [];

        foreach ($groupedByField as $fieldName => $fieldRows) {
            // Determine if the field is a boolean type field
            $isBoolean = false;
            if (in_array(strtolower($fieldName), ['is_good', 'is_active', 'ရ/မရ', 'ရ/ မရ', 'status']) || 
                str_contains(strtolower($fieldName), 'pass') || 
                str_contains(strtolower($fieldName), 'fail')) {
                $isBoolean = true;
            }

            if (!$isBoolean) {
                // If all non-empty values are strictly boolean-like (0, 1, true, false, yes, no)
                $allBooleanValues = true;
                foreach ($fieldRows as $row) {
                    $act = strtolower(trim((string)$row->actual_value));
                    $exp = strtolower(trim((string)$row->expected_value));
                    if (($act !== '' && !in_array($act, ['0', '1', 'true', 'false', 'yes', 'no'])) || 
                        ($exp !== '' && !in_array($exp, ['0', '1', 'true', 'false', 'yes', 'no']))) {
                        $allBooleanValues = false;
                        break;
                    }
                }
                if ($allBooleanValues) {
                    $isBoolean = true;
                }
            }

            if ($isBoolean) {
                // Handle Boolean metrics
                $branchesBoolean = [];
                foreach ($fieldRows as $row) {
                    $branchName = $row->branch_name ?: 'No Branch';
                    if (!isset($branchesBoolean[$branchName])) {
                        $branchesBoolean[$branchName] = [
                            'true_to_false_count' => 0,
                            'true_to_false_weight' => 0.0,
                            'false_to_true_count' => 0,
                            'false_to_true_weight' => 0.0,
                        ];
                    }

                    // Determine item weight
                    $weight = 0.0;
                    $hist = DB::table('validation_histories as vh')
                        ->join('validation_rules as vr', 'vh.rule_id', '=', 'vr.id')
                        ->join('purchase_items as pi', function($join) {
                            $join->on('vh.validatable_id', '=', 'pi.id')
                                 ->where('vh.validatable_type', '=', \App\Modules\Purchase\Models\PurchaseItem::class);
                        })
                        ->where('pi.purchase_request_id', '=', $row->purchase_request_id)
                        ->where('vh.status', '=', 'FAIL')
                        ->where(fn($q) => $q->where('vr.label', $fieldName)->orWhere('vr.field_name', $fieldName))
                        ->select('pi.dynamic_fields_json')
                        ->first();

                    if ($hist && $hist->dynamic_fields_json) {
                        $fields = json_decode($hist->dynamic_fields_json, true);
                        $weight = (float)($fields['goldWeightGram'] ?? 0);
                    }

                    $actVal = strtolower(trim((string)$row->actual_value));
                    $expVal = strtolower(trim((string)$row->expected_value));

                    $isExpectedTrue = in_array($expVal, ['1', 'true', 'yes']);
                    $isActualTrue = in_array($actVal, ['1', 'true', 'yes']);

                    if ($isExpectedTrue && !$isActualTrue) {
                        $branchesBoolean[$branchName]['true_to_false_count']++;
                        $branchesBoolean[$branchName]['true_to_false_weight'] += $weight;
                    } elseif (!$isExpectedTrue && $isActualTrue) {
                        $branchesBoolean[$branchName]['false_to_true_count']++;
                        $branchesBoolean[$branchName]['false_to_true_weight'] += $weight;
                    }
                }

                // Sum parent totals
                $parentTFC = 0;
                $parentTFW = 0.0;
                $parentFTC = 0;
                $parentFTW = 0.0;
                foreach ($branchesBoolean as $brName => $brVal) {
                    $parentTFC += $brVal['true_to_false_count'];
                    $parentTFW += $brVal['true_to_false_weight'];
                    $parentFTC += $brVal['false_to_true_count'];
                    $parentFTW += $brVal['false_to_true_weight'];
                }

                $booleanReport[$fieldName] = [
                    'true_to_false_count' => $parentTFC,
                    'true_to_false_weight' => $parentTFW,
                    'false_to_true_count' => $parentFTC,
                    'false_to_true_weight' => $parentFTW,
                    'branches' => $branchesBoolean
                ];
            } else {
                // Determine if the field is a number by checking if all non-empty values are numeric
                $isNumeric = true;
                $hasValues = false;
                foreach ($fieldRows as $row) {
                    $actualClean = $this->cleanNumeric($row->actual_value);
                    $expectedClean = $this->cleanNumeric($row->expected_value);

                    if (($actualClean !== null && $actualClean !== '') || ($expectedClean !== null && $expectedClean !== '')) {
                        $hasValues = true;
                        if (($actualClean !== null && $actualClean !== '' && !is_numeric($actualClean)) ||
                            ($expectedClean !== null && $expectedClean !== '' && !is_numeric($expectedClean))) {
                            $isNumeric = false;
                            break;
                        }
                    }
                }

                if (!$hasValues) {
                    $isNumeric = false;
                }

                // Group by branch under this field
                $branchesData = [];
                foreach ($fieldRows as $row) {
                    $branchName = $row->branch_name ?: 'No Branch';
                    if (!isset($branchesData[$branchName])) {
                        $branchesData[$branchName] = [
                            'actual_sum' => 0.0,
                            'expected_sum' => 0.0,
                            'count' => 0,
                        ];
                    }

                    $actualClean = $this->cleanNumeric($row->actual_value);
                    $expectedClean = $this->cleanNumeric($row->expected_value);

                    if ($isNumeric) {
                        $branchesData[$branchName]['actual_sum'] += (float) ($actualClean ?? 0);
                        $branchesData[$branchName]['expected_sum'] += (float) ($expectedClean ?? 0);
                    }
                    $branchesData[$branchName]['count']++;
                }

                // Calculate parent totals
                $parentActual = 0.0;
                $parentExpected = 0.0;
                $parentCount = 0;
                foreach ($branchesData as $brName => $brVal) {
                    $parentActual += $brVal['actual_sum'];
                    $parentExpected += $brVal['expected_sum'];
                    $parentCount += $brVal['count'];
                }

                $reportData[$fieldName] = [
                    'is_numeric' => $isNumeric,
                    'actual_total' => $parentActual,
                    'expected_total' => $parentExpected,
                    'count_total' => $parentCount,
                    'branches' => $branchesData
                ];
            }
        }

        return [
            'states' => $states,
            'reportData' => $reportData,
            'booleanReport' => $booleanReport,
        ];
    }

    private function cleanNumeric($val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }
        $cleaned = str_replace([',', ' '], '', (string)$val);
        return $cleaned;
    }
}
