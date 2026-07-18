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

        $query = DB::table('validation_histories as vh')
            ->join('validation_rules as vr', 'vh.rule_id', '=', 'vr.id')
            ->leftJoin('purchase_items as pi', function($join) {
                $join->on('vh.validatable_id', '=', 'pi.id')
                     ->where('vh.validatable_type', '=', \App\Modules\Purchase\Models\PurchaseItem::class);
            })
            ->join('purchase_requests as pr', function ($join) {
                $join->on(function ($query) {
                    $query->on('vh.validatable_id', '=', 'pr.id')
                          ->where('vh.validatable_type', '=', \App\Modules\Purchase\Models\PurchaseRequest::class);
                })
                ->orOn(function ($query) {
                    $query->on('pi.purchase_request_id', '=', 'pr.id')
                          ->where('vh.validatable_type', '=', \App\Modules\Purchase\Models\PurchaseItem::class);
                });
            })
            ->leftJoin('branches as b', 'pr.branch_id', '=', 'b.id')
            ->leftJoin('workflow_states as ws', 'pr.workflow_state_id', '=', 'ws.id')
            ->where('vh.status', '=', 'FAIL')
            ->whereNull('pr.deleted_at');

        // Apply Workflow filter
        if ($this->workflowFilterMode === 'end_states') {
            $query->where('ws.is_end', true);
        } elseif ($this->workflowFilterMode === 'specific' && $this->selectedStateId) {
            $query->where('pr.workflow_state_id', $this->selectedStateId);
        }

        // Apply Date Range filter
        if ($this->startDate) {
            $query->whereDate('vh.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('vh.created_at', '<=', $this->endDate);
        }

        $rows = $query->select([
            'vr.label as rule_label',
            'vr.field_name as rule_field',
            'b.name as branch_name',
            'vh.input_value',
            'vh.expected_value',
        ])->get();

        // Group rows by field name and analyze if the field is numeric
        $groupedByField = [];
        foreach ($rows as $row) {
            $fieldName = $row->rule_label ?: $row->rule_field ?: 'Unknown Field';
            if (!isset($groupedByField[$fieldName])) {
                $groupedByField[$fieldName] = [];
            }
            $groupedByField[$fieldName][] = $row;
        }

        $reportData = [];
        foreach ($groupedByField as $fieldName => $fieldRows) {
            // Determine if the field is a number by checking if all non-empty values are numeric
            $isNumeric = true;
            $hasValues = false;
            foreach ($fieldRows as $row) {
                $actualClean = $this->cleanNumeric($row->input_value);
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

                $actualClean = $this->cleanNumeric($row->input_value);
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

        return [
            'states' => $states,
            'reportData' => $reportData,
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
