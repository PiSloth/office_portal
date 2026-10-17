<?php

namespace App\Services;

use App\Filament\Repurchase\Pages\DecisionReport;
use App\Modules\Core\Validation\Models\ValidationHistory;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RepurchaseCheckHistoryExportService
{
    /**
     * Stream download the field-level checked history Excel (.xlsx) file.
     */
    public function downloadExcel(
        Builder|Collection|null $source = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $selectedState = 'all',
        ?string $selectedField = 'all'
    ): StreamedResponse {
        $filename = 'repurchase-checked-history-' . now()->format('Y-m-d-His') . '.xlsx';

        $rows = $this->buildReportRows($source, $startDate, $endDate, $selectedState, $selectedField);

        return response()->streamDownload(function () use ($rows) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Checked History');

            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(31, 41, 55));

            $headers = self::getHeaders();
            $writer->addRow(Row::fromValues($headers, $headerStyle));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Return the standard Excel column headers in exact order.
     */
    public static function getHeaders(): array
    {
        return [
            'Branch Name',
            'Created At',
            'Customer Name',
            'Purchase Number',
            'Inventory Name',
            'Checked Field',
            'Verify',
            'Supervisor Check',
            'Inv Validate',
            'Duration (Verify - Supervisor Check)',
            'Duration (Supervisor Check - Inv Validate)',
            'Total Lead Day',
            'Late Check',
            'Pickup Remark',
            'QC Profit',
            'Who Unagree',
            'Status',
        ];
    }

    /**
     * Build the structured rows for export (and testing).
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildReportRows(
        Builder|Collection|null $source = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $selectedState = 'all',
        ?string $selectedField = 'all'
    ): array {
        $purchaseRequests = $this->resolvePurchaseRequests($source, $startDate, $endDate, $selectedState);

        $rows = [];

        foreach ($purchaseRequests as $pr) {
            $branchName = $pr->branch?->name ?: ($pr->branch?->code ?: 'N/A');
            $createdAtDate = $pr->created_at ? Carbon::parse($pr->created_at)->format('Y-m-d') : '-';
            $customerName = $pr->customer_name ?: '-';
            $purchaseNumber = $pr->purchase_number ?: "PR-{$pr->id}";

            foreach ($pr->items as $item) {
                $itemFields = is_array($item->dynamic_fields_json) ? $item->dynamic_fields_json : [];
                $inventoryName = $itemFields['product_name'] ?? '-';

                // Combine item validation histories and any request-level validation histories
                $histories = collect();
                if ($item->relationLoaded('validationHistories')) {
                    $histories = $histories->concat($item->validationHistories);
                } else {
                    $histories = $histories->concat(
                        $item->validationHistories()->with(['rule.ruleSet', 'ruleSet', 'workflowState', 'user'])->get()
                    );
                }

                if ($histories->isEmpty()) {
                    continue;
                }

                // Sort chronologically (oldest to newest)
                $histories = $histories->sortBy([
                    fn ($a, $b) => ($a->created_at?->timestamp ?? 0) <=> ($b->created_at?->timestamp ?? 0),
                    fn ($a, $b) => $a->id <=> $b->id,
                ])->values();

                // Group histories by canonical field key so all 3 stages of the same field merge into 1 row
                $groupedByField = [];
                foreach ($histories as $vh) {
                    $rawField = $vh->rule ? ($vh->rule->field_name ?: $vh->rule->label) : null;
                    if (! $rawField) {
                        continue;
                    }

                    $canonInfo = self::getFieldCanonicalInfo($rawField);
                    $canonKey = $canonInfo['key'];

                    if ($selectedField && $selectedField !== 'all') {
                        $selCanonKey = self::getFieldCanonicalInfo($selectedField)['key'];
                        if ($canonKey !== $selCanonKey && strtolower($rawField) !== strtolower($selectedField)) {
                            continue;
                        }
                    }

                    if (! isset($groupedByField[$canonKey])) {
                        $groupedByField[$canonKey] = [
                            'canon_key' => $canonKey,
                            'canon_label' => $canonInfo['label'],
                            'raw_field' => $vh->rule?->field_name ?: $rawField,
                            'histories' => collect(),
                        ];
                    }

                    $groupedByField[$canonKey]['histories']->push($vh);
                }

                foreach ($groupedByField as $canonKey => $fieldGroup) {
                    $fieldHistories = $fieldGroup['histories'];
                    $rawFieldName = $fieldGroup['raw_field'];
                    $fieldLabel = $fieldGroup['canon_label'];

                    $stages = $this->classifyHistoriesIntoStages($fieldHistories);

                    /** @var ValidationHistory|null $verifyHistory */
                    $verifyHistory = $stages['verify'];
                    /** @var ValidationHistory|null $supHistory */
                    $supHistory = $stages['supervisor_check'];
                    /** @var ValidationHistory|null $invHistory */
                    $invHistory = $stages['inv_validate'];

                    $isBoolean = self::isBooleanField($canonKey, $rawFieldName, $fieldHistories, $itemFields);

                    // 1. Resolve Expected Value for "Verify" column
                    $rawExpected = $verifyHistory?->expected_value
                        ?? $supHistory?->expected_value
                        ?? $invHistory?->expected_value
                        ?? ($itemFields[$rawFieldName] ?? null);

                    $fallbackItemVal = $itemFields[$rawFieldName] ?? ($itemFields['is_good'] ?? null);

                    if ($isBoolean) {
                        $verifyDisplay = self::formatBooleanValue($rawExpected, $fallbackItemVal) ?? 'false';
                    } else {
                        $verifyDisplay = ($rawExpected !== null && $rawExpected !== '') ? (string) $rawExpected : '-';
                    }

                    // 2. Resolve Input Value for "Supervisor Check" column (show "Pending" if no value)
                    $supDisplay = 'Pending';
                    $supBool = null;
                    if ($supHistory) {
                        if ($isBoolean) {
                            $supBool = self::resolveHistoryBooleanInput($supHistory, $verifyDisplay);
                            $supDisplay = $supBool ?? 'Pending';
                        } else {
                            $supDisplay = ($supHistory->input_value !== null && $supHistory->input_value !== '')
                                ? (string) $supHistory->input_value
                                : 'Pending';
                        }
                    }

                    // 3. Resolve Input Value for "Inv Validate" column (show "Pending" if no value)
                    $invDisplay = 'Pending';
                    $invBool = null;
                    if ($invHistory) {
                        if ($isBoolean) {
                            $invBool = self::resolveHistoryBooleanInput($invHistory, $verifyDisplay);
                            $invDisplay = $invBool ?? 'Pending';
                        } else {
                            $invDisplay = ($invHistory->input_value !== null && $invHistory->input_value !== '')
                                ? (string) $invHistory->input_value
                                : 'Pending';
                        }
                    }

                    // 4. Calculate Durations in Days, Total Lead Day, Late Check & Pickup Remark
                    $verifyTimestamp = $verifyHistory?->created_at
                        ?? $pr->submitted_at
                        ?? $item->created_at
                        ?? $pr->created_at;
                    $supTimestamp = $supHistory?->created_at;
                    $invTimestamp = $invHistory?->created_at;

                    $daysVerifyToSup = self::calculateDurationDaysInt($verifyTimestamp, $supTimestamp);
                    $daysSupToInv = self::calculateDurationDaysInt($supTimestamp, $invTimestamp);

                    $durationVerifyToSup = self::formatDaysLabel($daysVerifyToSup);
                    $durationSupToInv = self::formatDaysLabel($daysSupToInv);

                    $totalLeadDaysInt = ($daysVerifyToSup !== null && $daysSupToInv !== null)
                        ? ($daysVerifyToSup + $daysSupToInv)
                        : null;
                    $totalLeadDay = self::formatDaysLabel($totalLeadDaysInt);

                    $lateCheck = '-';
                    if ($daysVerifyToSup !== null) {
                        $lateCheck = $daysVerifyToSup > 0 ? 'true' : 'false';
                    }

                    $pickupRemark = ($lateCheck === 'true' && $daysSupToInv === 0)
                        ? 'Same day with pickup'
                        : '-';

                    // 5. Calculate QC Profit & Who Unagree for Boolean fields
                    $qcProfit = '-';
                    $whoUnagree = '-';
                    if ($isBoolean) {
                        // Compare Expected (Verify) against the latest checked stage input (Inv Validate -> Supervisor Check -> Verify Input)
                        $verifyInputBool = $verifyHistory ? self::resolveHistoryBooleanInput($verifyHistory, $verifyDisplay) : null;
                        $latestCheckedBool = $invBool ?? $supBool ?? $verifyInputBool;

                        if ($verifyDisplay === 'false' && $latestCheckedBool === 'true') {
                            $qcProfit = 'good';
                        } elseif ($verifyDisplay === 'true' && $latestCheckedBool === 'false') {
                            $qcProfit = 'Loss';
                        }

                        $whoUnagree = self::resolveWhoUnagree($verifyDisplay, $supBool, $invBool);
                    }

                    // 6. Resolve Pass/Fail Status (Final Column)
                    $status = $this->resolveOverallStatus($verifyHistory, $supHistory, $invHistory, $fieldHistories);

                    $rows[] = [
                        'branch_name' => $branchName,
                        'created_at' => $createdAtDate,
                        'customer_name' => $customerName,
                        'purchase_number' => $purchaseNumber,
                        'inventory_name' => $inventoryName,
                        'checked_field' => $fieldLabel,
                        'verify' => $verifyDisplay,
                        'supervisor_check' => $supDisplay,
                        'inv_validate' => $invDisplay,
                        'duration_verify_to_supervisor' => $durationVerifyToSup,
                        'duration_supervisor_to_inv' => $durationSupToInv,
                        'total_lead_day' => $totalLeadDay,
                        'late_check' => $lateCheck,
                        'pickup_remark' => $pickupRemark,
                        'qc_profit' => $qcProfit,
                        'who_unagree' => $whoUnagree,
                        'status' => $status,
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * Identify which of the 3 members (Cashier / Supervisor / Inv Validate) disagrees with the other 2
     * on a boolean check field.
     */
    public static function resolveWhoUnagree(?string $verifyBool, ?string $supBool, ?string $invBool): string
    {
        if (
            ! in_array($verifyBool, ['true', 'false'], true) ||
            ! in_array($supBool, ['true', 'false'], true) ||
            ! in_array($invBool, ['true', 'false'], true)
        ) {
            return '-';
        }

        // All 3 agree
        if ($verifyBool === $supBool && $supBool === $invBool) {
            return '-';
        }

        // Supervisor disagrees with Cashier (Verify) and Inv Validate (e.g. true, false, true OR false, true, false)
        if ($verifyBool === $invBool && $supBool !== $verifyBool) {
            return 'Supervisor unagree';
        }

        // Cashier (Verify) disagrees with Supervisor and Inv Validate (e.g. false, true, true OR true, false, false)
        if ($supBool === $invBool && $verifyBool !== $supBool) {
            return 'Cashier unagree';
        }

        // Inv Validate disagrees with Cashier (Verify) and Supervisor (e.g. true, true, false OR false, false, true)
        if ($verifyBool === $supBool && $invBool !== $verifyBool) {
            return 'Inv Validate unagree';
        }

        return '-';
    }

    /**
     * Load PurchaseRequests with necessary relations based on query, collection, or filters.
     */
    protected function resolvePurchaseRequests(
        Builder|Collection|null $source,
        ?string $startDate,
        ?string $endDate,
        ?string $selectedState
    ): Collection {
        $relations = [
            'branch',
            'workflowState',
            'items.validationHistories.rule.ruleSet',
            'items.validationHistories.ruleSet',
            'items.validationHistories.workflowState',
            'items.validationHistories.user',
        ];

        if ($source instanceof Collection) {
            if (method_exists($source, 'loadMissing')) {
                $source->loadMissing($relations);
            }

            if ($startDate) {
                $startBound = Carbon::parse($startDate)->startOfDay();
                $source = $source->filter(fn ($pr) => $pr->created_at && Carbon::parse($pr->created_at)->gte($startBound));
            }

            if ($endDate) {
                $endBound = Carbon::parse($endDate)->endOfDay();
                $source = $source->filter(fn ($pr) => $pr->created_at && Carbon::parse($pr->created_at)->lte($endBound));
            }

            return $source->values();
        }

        $query = $source instanceof Builder
            ? (clone $source)->with($relations)
            : PurchaseRequest::query()->with($relations);

        $query->whereHas('items.validationHistories');

        if ($selectedState === 'end_states') {
            $query->where(function ($sq) {
                $sq->whereHas('workflowState', fn ($wsq) => $wsq->where('is_end', true))
                    ->orWhereHas('items.validationHistories.workflowState', fn ($wsq) => $wsq->where('is_end', true));
            });
        } elseif (! empty($selectedState) && $selectedState !== 'all') {
            $stateId = $selectedState;
            $query->where(function ($sq) use ($stateId) {
                $sq->where('workflow_state_id', $stateId)
                    ->orWhereHas('items.validationHistories', fn ($wsq) => $wsq->where('workflow_state_id', $stateId));
            });
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->orderByDesc('id')->get();
    }

    /**
     * Cache of workflow transitions keyed by from_state_id.
     *
     * @var array<int, Collection>|null
     */
    protected ?array $transitionsByFromState = null;

    /**
     * Whether the workflow's Inventory Validate state has its own outgoing validation transition.
     */
    protected ?bool $invStateHasOutgoingValidation = null;

    /**
     * Load and cache workflow transitions metadata.
     */
    protected function ensureWorkflowTransitionMetadataLoaded(): void
    {
        if ($this->transitionsByFromState !== null) {
            return;
        }

        $this->transitionsByFromState = [];
        $this->invStateHasOutgoingValidation = false;

        try {
            $transitions = \App\Modules\Core\Workflow\Models\WorkflowTransition::with(['fromState', 'toState'])->get();
            foreach ($transitions as $tr) {
                $fromId = (int) $tr->from_state_id;
                if (! isset($this->transitionsByFromState[$fromId])) {
                    $this->transitionsByFromState[$fromId] = collect();
                }
                $this->transitionsByFromState[$fromId]->push($tr);

                $fromName = strtolower(trim((string) ($tr->fromState?->name ?? '')));
                if (
                    $tr->validation_rule_set_id &&
                    (str_contains($fromName, 'inv') || str_contains($fromName, 'validate'))
                ) {
                    $this->invStateHasOutgoingValidation = true;
                }
            }
        } catch (\Throwable $e) {
            $this->transitionsByFromState = [];
            $this->invStateHasOutgoingValidation = false;
        }
    }

    /**
     * Classify a field's validation histories into the 3 workflow stages:
     * 'verify', 'supervisor_check', and 'inv_validate'.
     *
     * @return array{verify: ?ValidationHistory, supervisor_check: ?ValidationHistory, inv_validate: ?ValidationHistory}
     */
    protected function classifyHistoriesIntoStages(Collection $fieldHistories): array
    {
        $this->ensureWorkflowTransitionMetadataLoaded();

        $stages = [
            'verify' => null,
            'supervisor_check' => null,
            'inv_validate' => null,
        ];

        // Check if any history directly has an 'inv' / 'validate' state name
        $hasDirectInvState = $fieldHistories->contains(function ($vh) {
            $sName = strtolower(trim((string) ($vh->workflowState?->name ?? '')));
            return str_contains($sName, 'inv') || str_contains($sName, 'validate');
        });

        $useTransitionTargetState = ! $hasDirectInvState && ! $this->invStateHasOutgoingValidation;

        $unmatched = [];

        foreach ($fieldHistories as $vh) {
            $stateName = strtolower(trim((string) ($vh->workflowState?->name ?? '')));
            $ruleSetName = strtolower(trim((string) ($vh->ruleSet?->name ?? ($vh->rule?->ruleSet?->name ?? ''))));

            // 1. Check if RuleSet name explicitly identifies the stage
            if (str_contains($ruleSetName, 'inv') || str_contains($ruleSetName, 'validate')) {
                $stages['inv_validate'] = $vh;
                continue;
            }
            if (str_contains($ruleSetName, 'supervisor') || str_contains($ruleSetName, 'sup')) {
                $stages['supervisor_check'] = $vh;
                continue;
            }

            // 2. If EditPurchaseRequest logged from_state_id and the final state is a to_state without outgoing validation transitions,
            // check if a matching transition's toState / action_name identifies the stage
            $effectiveStateName = $stateName;
            if ($useTransitionTargetState && $vh->workflow_state_id && ! empty($this->transitionsByFromState[(int) $vh->workflow_state_id])) {
                $candidates = $this->transitionsByFromState[(int) $vh->workflow_state_id];
                $matchedTransition = $candidates->first(function ($tr) use ($vh) {
                    return $vh->validation_rule_set_id && (int) $tr->validation_rule_set_id === (int) $vh->validation_rule_set_id;
                }) ?? $candidates->first(fn ($tr) => ! empty($tr->validation_rule_set_id));

                if ($matchedTransition && $matchedTransition->toState) {
                    $toStateName = strtolower(trim((string) $matchedTransition->toState->name));
                    $actionName = strtolower(trim((string) $matchedTransition->action_name));
                    // Only shift to toState if the workflow actually has a Supervisor/Inventory transition chain
                    $hasSupOrInvTarget = collect($this->transitionsByFromState)->flatten(1)->contains(function ($tr) {
                        $tName = strtolower(trim((string) ($tr->toState?->name ?? '')));
                        return str_contains($tName, 'inv') || str_contains($tName, 'validate');
                    });
                    if ($hasSupOrInvTarget) {
                        $effectiveStateName = $toStateName . ' ' . $actionName;
                    }
                }
            }

            if (str_contains($effectiveStateName, 'inv') || str_contains($effectiveStateName, 'validate')) {
                $stages['inv_validate'] = $vh;
            } elseif (str_contains($effectiveStateName, 'supervisor') || str_contains($effectiveStateName, 'sup')) {
                $stages['supervisor_check'] = $vh;
            } elseif (
                str_contains($effectiveStateName, 'verif') ||
                str_contains($effectiveStateName, 'submit') ||
                str_contains($effectiveStateName, 'draft') ||
                str_contains($ruleSetName, 'verif')
            ) {
                $stages['verify'] = $vh;
            } else {
                $unmatched[] = $vh;
            }
        }

        // Fallback for any histories whose state/ruleSet name uses custom labels
        foreach ($unmatched as $vh) {
            if ($stages['verify'] === null) {
                $stages['verify'] = $vh;
            } elseif ($stages['supervisor_check'] === null) {
                $stages['supervisor_check'] = $vh;
            } elseif ($stages['inv_validate'] === null) {
                $stages['inv_validate'] = $vh;
            }
        }

        return $stages;
    }

    /**
     * Determine the overall PASS / FAIL status for the row.
     */
    protected function resolveOverallStatus(
        ?ValidationHistory $verifyHistory,
        ?ValidationHistory $supHistory,
        ?ValidationHistory $invHistory,
        Collection $allFieldHistories
    ): string {
        // Check from latest stage backwards, or check if any stage failed
        $activeHistories = array_filter([$invHistory, $supHistory, $verifyHistory]);

        foreach ($activeHistories as $h) {
            if (strtoupper((string) $h->status) === 'FAIL') {
                return 'FAIL';
            }
        }

        if ($allFieldHistories->contains(fn ($h) => strtoupper((string) $h->status) === 'FAIL')) {
            return 'FAIL';
        }

        return 'PASS';
    }

    /**
     * Calculate duration between two timestamps in integer calendar days.
     */
    public static function calculateDurationDaysInt($from, $to): ?int
    {
        if (! $from || ! $to) {
            return null;
        }

        try {
            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->startOfDay();

            return (int) abs($end->diffInDays($start));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Format integer day count as label (e.g. "2 Days", "1 Day", "0 Day", or "-").
     */
    public static function formatDaysLabel(?int $days): string
    {
        if ($days === null) {
            return '-';
        }

        return $days . ' ' . ($days <= 1 ? 'Day' : 'Days');
    }

    /**
     * Format duration between two timestamps in days (e.g. "2 Days", "1 Day", "0 Day").
     */
    public static function formatDurationDays($from, $to): string
    {
        return self::formatDaysLabel(self::calculateDurationDaysInt($from, $to));
    }

    /**
     * Determine if a checked field is a boolean type (e.g. ရ/မရ, is_good).
     */
    public static function isBooleanField(string $canonKey, string $rawFieldName, Collection $histories, array $itemFields = []): bool
    {
        $normCanon = strtolower($canonKey);
        $normRaw = strtolower($rawFieldName);

        if (
            in_array($normCanon, ['ရ/မရ', 'ရ/ မရ', 'is_good', 'is_active'], true) ||
            in_array($normRaw, ['ရ/မရ', 'ရ/ မရ', 'is_good', 'is_active'], true) ||
            str_contains($normCanon, 'ရမရ') ||
            str_contains($normRaw, 'is_good') ||
            str_contains($normRaw, 'ရ/မရ')
        ) {
            return true;
        }

        if (array_key_exists($rawFieldName, $itemFields) && is_bool($itemFields[$rawFieldName])) {
            return true;
        }

        return false;
    }

    /**
     * Convert raw boolean representation ('1', '0', 'ရ', 'မရ', true, false) into 'true' or 'false'.
     */
    public static function formatBooleanValue($value, $fallbackValue = null): ?string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value !== null && $value !== '') {
            $s = strtolower(trim((string) $value));
            if (in_array($s, ['1', 'true', 'yes', 'y', 'ရ', 'pass'], true)) {
                return 'true';
            }
            if (in_array($s, ['0', 'false', 'no', 'n', 'မရ', 'fail'], true)) {
                return 'false';
            }
        }

        if ($fallbackValue !== null && $fallbackValue !== '') {
            if (is_bool($fallbackValue)) {
                return $fallbackValue ? 'true' : 'false';
            }
            $s = strtolower(trim((string) $fallbackValue));
            if (in_array($s, ['1', 'true', 'yes', 'y', 'ရ', 'pass'], true)) {
                return 'true';
            }
            if (in_array($s, ['0', 'false', 'no', 'n', 'မရ', 'fail'], true)) {
                return 'false';
            }
        }

        // In PHP, casting boolean false to string produces ''
        if ($value === '' || $fallbackValue === false) {
            return 'false';
        }

        return null;
    }

    /**
     * Resolve boolean input value ('true' or 'false') from a ValidationHistory record.
     */
    public static function resolveHistoryBooleanInput(ValidationHistory $history, string $expectedBool): ?string
    {
        if ($history->input_value !== null && $history->input_value !== '') {
            $parsed = self::formatBooleanValue($history->input_value);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        // If input_value was empty (e.g. non-required rule where user selected PASS/FAIL directly)
        if ($history->status === 'PASS') {
            return $expectedBool;
        }
        if ($history->status === 'FAIL') {
            return $expectedBool === 'true' ? 'false' : 'true';
        }

        return null;
    }

    /**
     * Canonical field key & label resolution.
     *
     * @return array{key: string, label: string}
     */
    public static function getFieldCanonicalInfo(?string $rawField): array
    {
        if (empty($rawField)) {
            return ['key' => 'unknown', 'label' => 'Unknown'];
        }

        $norm = strtolower(str_replace(['_', '-', ' ', '(', ')', '/', '.'], '', trim($rawField)));

        if ($norm === 'pae' || $norm === 'ပဲ' || $norm === 'ပဲချိန်') {
            return [
                'key' => 'ပဲ-ချိန်',
                'label' => 'ပဲ-ချိန် (Pae Weight)',
            ];
        }

        return DecisionReport::getCanonicalFieldInfo($rawField);
    }
}
