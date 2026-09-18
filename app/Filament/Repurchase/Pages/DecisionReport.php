<?php

namespace App\Filament\Repurchase\Pages;

use App\Modules\Purchase\Models\PurchaseDecision;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DecisionReport extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Purchase';

    protected static ?string $navigationLabel = 'Decision Report';

    protected static ?string $title = 'Repurchase Performance Analysis';

    protected string $view = 'filament.repurchase.pages.decision-report';

    protected static ?int $navigationSort = 3;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $companyTitle = null;

    public string $selectedState = 'all'; // 'all', 'end_states', or specific workflow_state_id

    public ?string $toleranceField = 'all';

    public $toleranceValue = 0.05;

    public string $toleranceMode = 'excluded';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->companyTitle = $this->getCompanyTitle();
        $this->selectedState = 'all';
        $this->toleranceField = 'all';
        $this->toleranceValue = 0.05;
        $this->toleranceMode = 'excluded';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole(['super-admin', 'Super Admin'])
            || $user->can('reports.view')
            || $user->can('decisions.view')
            || $user->can('purchase-requests.view');
    }

    public function setPreset(string $preset): void
    {
        switch ($preset) {
            case 'today':
                $this->startDate = now()->toDateString();
                $this->endDate = now()->toDateString();
                break;
            case 'this_week':
                $this->startDate = now()->startOfWeek()->toDateString();
                $this->endDate = now()->endOfWeek()->toDateString();
                break;
            case 'this_month':
                $this->startDate = now()->startOfMonth()->toDateString();
                $this->endDate = now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->startDate = now()->subMonth()->startOfMonth()->toDateString();
                $this->endDate = now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_year':
                $this->startDate = now()->startOfYear()->toDateString();
                $this->endDate = now()->toDateString();
                break;
            case 'all':
                $this->startDate = null;
                $this->endDate = null;
                break;
        }
    }

    public function isToleranceFilterActive(): bool
    {
        return $this->toleranceValue !== null && $this->toleranceValue !== '' && is_numeric($this->toleranceValue);
    }

    public function resetTolerance(): void
    {
        $this->toleranceField = 'all';
        $this->toleranceValue = 0.05;
        $this->toleranceMode = 'excluded';
    }

    public function getAvailableStates(): array
    {
        $states = [
            'all' => 'စစ်ဆေးချက် အားလုံး (All Statuses)',
            'end_states' => 'အပြီးသတ် အဆင့်များ (End States - Rejected / Paid)',
        ];

        try {
            $wfStates = \App\Modules\Core\Workflow\Models\WorkflowState::orderBy('id')->get();
            foreach ($wfStates as $ws) {
                $states[(string)$ws->id] = $ws->name . ($ws->is_end ? ' (End State)' : '');
            }
        } catch (\Throwable $e) {
        }

        return $states;
    }

    public function getStateLabel(): string
    {
        $available = $this->getAvailableStates();
        return $available[$this->selectedState] ?? 'All Statuses';
    }

    public static function getCanonicalFieldInfo(?string $rawField): array
    {
        if (empty($rawField)) {
            return [
                'key' => 'unknown',
                'label' => 'Unknown',
            ];
        }

        $raw = trim($rawField);
        if ($raw === 'all') {
            return [
                'key' => 'all',
                'label' => 'စစ်ဆေးချက်အားလုံး (All Fields)',
            ];
        }

        $norm = strtolower(str_replace(['_', '-', ' ', '(', ')', '/', '.'], '', $raw));

        // 1. Stone weight (Must check before weight and yawe)
        if (str_contains($norm, 'ကျောက်') || str_contains($norm, 'kyauk') || str_contains($norm, 'stone')) {
            return [
                'key' => 'ကျောက်-ချိန်',
                'label' => 'ကျောက်-ချိန် (Stone Weight)',
            ];
        }

        // 2. Weight Gram
        if (str_contains($norm, 'weight') || str_contains($norm, 'အလေးချိန်') || str_contains($norm, 'gram')) {
            return [
                'key' => 'weight_gram',
                'label' => 'အလေးချိန် (Weight Gram)',
            ];
        }

        // 3. Kyat weight
        if (str_contains($norm, 'ကျပ်') || str_contains($norm, 'kyat')) {
            return [
                'key' => 'ကျပ်-ချိန်',
                'label' => 'ကျပ်-ချိန် (Kyat Weight)',
            ];
        }

        // 4. Yawe weight
        if (str_contains($norm, 'ရွေး') || str_contains($norm, 'yawe')) {
            return [
                'key' => 'ရွေး-ချိန်',
                'label' => 'ရွေး-ချိန် (Yawe Weight)',
            ];
        }

        // 5. Percent Deduction
        if (str_contains($norm, 'ရာခိုင်နှုန်း') || str_contains($norm, 'percent')) {
            return [
                'key' => 'ရာခိုင်နှုန်းလျော့',
                'label' => 'ရာခိုင်နှုန်းလျော့ (Percent Deduction)',
            ];
        }

        // 6. Quantity
        if (str_contains($norm, 'quantity') || str_contains($norm, 'qty') || str_contains($norm, 'အရေအတွက်')) {
            return [
                'key' => 'quantity',
                'label' => 'အရေအတွက် (Quantity)',
            ];
        }

        // 7. Gold Grade
        if (str_contains($norm, 'grade') || str_contains($norm, 'ရွှေရည်') || str_contains($norm, 'goldlist')) {
            return [
                'key' => 'gold_grade',
                'label' => 'ရွှေရည် (Gold Grade)',
            ];
        }

        // 8. Gold Quality
        if (str_contains($norm, 'quality') || str_contains($norm, 'အရည်အသွေး')) {
            return [
                'key' => 'gold_quality',
                'label' => 'ရွှေအရည်အသွေး (Gold Quality)',
            ];
        }

        // 9. Pass / Fail (ရ/မရ)
        if (str_contains($norm, 'ရမရ') || str_contains($norm, 'isgood') || str_contains($norm, 'pass')) {
            return [
                'key' => 'ရ/မရ',
                'label' => 'ရ/မရ (Pass/Fail)',
            ];
        }

        // 10. Expiry Date
        if (str_contains($norm, 'expiry') || str_contains($norm, 'သက်တမ်း')) {
            return [
                'key' => 'expiry_date',
                'label' => 'သက်တမ်းကုန်ဆုံးရက် (Expiry Date)',
            ];
        }

        // 11. IMEI
        if (str_contains($norm, 'imei')) {
            return [
                'key' => 'imei',
                'label' => 'IMEI နံပါတ်',
            ];
        }

        return [
            'key' => $raw,
            'label' => ucwords(str_replace('_', ' ', $raw)),
        ];
    }

    public function isFieldMatching(?string $ruleField, ?string $selectedField): bool
    {
        if (empty($selectedField) || $selectedField === 'all') {
            return true;
        }
        if (empty($ruleField)) {
            return false;
        }
        if (strtolower(trim($ruleField)) === strtolower(trim($selectedField))) {
            return true;
        }

        $ruleCanon = self::getCanonicalFieldInfo($ruleField)['key'];
        $selCanon = self::getCanonicalFieldInfo($selectedField)['key'];

        return $ruleCanon === $selCanon;
    }

    public function isFailureMatchingTolerance(string $fieldName, $expectedValue, $actualValue): bool
    {
        // Must have expected value and actual checked value
        if ($expectedValue === null || $expectedValue === '' || $actualValue === null || $actualValue === '') {
            return false;
        }

        $isTargetField = $this->isFieldMatching($fieldName, $this->toleranceField);

        // If not matching selected target field
        if (! $isTargetField) {
            if ($this->toleranceMode === 'retrieved' && $this->toleranceField !== 'all') {
                return false;
            }
            return true;
        }

        if (! $this->isToleranceFilterActive()) {
            return true;
        }

        $cleanExp = preg_replace('/[^0-9.-]/', '', (string)$expectedValue);
        $cleanAct = preg_replace('/[^0-9.-]/', '', (string)$actualValue);

        if ($cleanExp !== '' && $cleanAct !== '' && is_numeric($cleanExp) && is_numeric($cleanAct)) {
            $diff = abs((float)$cleanExp - (float)$cleanAct);
            $tolValue = (float) $this->toleranceValue;
            $isWithin = ($diff <= $tolValue);

            return $this->toleranceMode === 'excluded' ? ! $isWithin : $isWithin;
        }

        return $this->toleranceMode === 'excluded';
    }

    public function getAvailableFailFields(): array
    {
        $numericGroup = [
            'weight_gram' => 'အလေးချိန် (Weight Gram)',
            'ကျပ်-ချိန်' => 'ကျပ်-ချိန် (Kyat Weight)',
            'ရွေး-ချိန်' => 'ရွေး-ချိန် (Yawe Weight)',
            'ကျောက်-ချိန်' => 'ကျောက်-ချိန် (Stone Weight)',
            'ရာခိုင်နှုန်းလျော့' => 'ရာခိုင်နှုန်းလျော့ (Percent Deduction)',
            'quantity' => 'အရေအတွက် (Quantity)',
        ];

        $otherGroup = [
            'gold_grade' => 'ရွှေရည် (Gold Grade)',
            'gold_quality' => 'ရွှေအရည်အသွေး (Gold Quality)',
            'ရ/မရ' => 'ရ/မရ (Pass/Fail)',
            'expiry_date' => 'သက်တမ်းကုန်ဆုံးရက် (Expiry Date)',
            'imei' => 'IMEI နံပါတ်',
        ];

        $dbFields = [];
        try {
            $fcFields = \App\Modules\Purchase\Models\FailCheck::select('field_name')
                ->whereNotNull('field_name')
                ->distinct()
                ->pluck('field_name')
                ->toArray();
            $dbFields = array_merge($dbFields, $fcFields);
        } catch (\Throwable $e) {
        }

        try {
            $rules = \App\Modules\Core\Validation\Models\ValidationRule::select('field_name', 'label')
                ->whereNotNull('field_name')
                ->distinct()
                ->get();
            foreach ($rules as $r) {
                if ($r->label) $dbFields[] = $r->label;
                if ($r->field_name) $dbFields[] = $r->field_name;
            }
        } catch (\Throwable $e) {
        }

        $isCovered = function (string $rawField) {
            $s = strtolower(str_replace(['_', '-', ' ', '(', ')', '/', '.'], '', $rawField));
            if (str_contains($s, 'weight') || str_contains($s, 'အလေးချိန်') || str_contains($s, 'gram')) return true;
            if (str_contains($s, 'ကျပ်')) return true;
            if (str_contains($s, 'ရွေး')) return true;
            if (str_contains($s, 'ကျောက်')) return true;
            if (str_contains($s, 'ရာခိုင်နှုန်း') || str_contains($s, 'percent')) return true;
            if (str_contains($s, 'quantity') || str_contains($s, 'qty') || str_contains($s, 'အရေအတွက်')) return true;
            if (str_contains($s, 'grade') || str_contains($s, 'ရွှေရည်')) return true;
            if (str_contains($s, 'quality') || str_contains($s, 'အရည်အသွေး')) return true;
            if (str_contains($s, 'ရမရ') || str_contains($s, 'isgood') || str_contains($s, 'pass')) return true;
            if (str_contains($s, 'expiry') || str_contains($s, 'သက်တမ်း')) return true;
            if (str_contains($s, 'imei')) return true;
            return false;
        };

        $seenLabels = array_flip(array_merge($numericGroup, $otherGroup));

        foreach (array_unique($dbFields) as $f) {
            if (empty($f) || $f === 'all' || $isCovered($f)) continue;
            $label = self::formatFieldLabel($f);
            if (! isset($seenLabels[$label])) {
                $otherGroup[$f] = $label;
                $seenLabels[$label] = true;
            }
        }

        return [
            'အလေးချိန်နှင့် ကိန်းဂဏန်းများ (Numeric / Weight Fields)' => $numericGroup,
            'အခြား စစ်ဆေးချက်များ (Other Specification Fields)' => $otherGroup,
        ];
    }

    public static function formatFieldLabel(string $fieldName): string
    {
        return self::getCanonicalFieldInfo($fieldName)['label'];
    }

    public function getReportData(): array
    {
        $query = PurchaseDecision::with([
            'purchaseRequest.branch',
            'purchaseRequest.workflowState',
            'purchaseRequest.failChecks',
            'purchaseRequest.items.validationHistories.rule',
            'purchaseRequest.validationHistories.rule',
        ])
            ->whereHas('purchaseRequest', function ($q) {
                $q->whereNull('deleted_at');

                if ($this->selectedState === 'end_states') {
                    $q->whereHas('workflowState', fn ($sq) => $sq->where('is_end', true));
                } elseif (! empty($this->selectedState) && $this->selectedState !== 'all') {
                    $q->where('workflow_state_id', $this->selectedState);
                }
            });

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        $decisions = $query->get();

        $table1Data = [];
        $table2Data = [];

        $allDistinctRepurchaseIds = [];
        $totalWrongFieldCount = 0;
        $totalOpenDecisionsCount = 0;

        foreach ($decisions as $decision) {
            $pr = $decision->purchaseRequest;
            if (! $pr) {
                continue;
            }

            $repurchaseId = $pr->id;
            $branchName = $pr->branch?->name ?: ($pr->branch?->code ?: 'သတ်မှတ်မထားသော ဌာနခွဲ (Unassigned Branch)');
            $isOpen = $decision->status === 'open';

            // Collect all wrong field occurrences on this specific purchase request
            $fieldOccurrences = [];

            // 1. From failChecks
            if ($pr->relationLoaded('failChecks')) {
                foreach ($pr->failChecks as $fc) {
                    if (! empty($fc->field_name)) {
                        if ($this->isFailureMatchingTolerance($fc->field_name, $fc->expected_value, $fc->actual_value)) {
                            $fieldOccurrences[$fc->field_name] = ($fieldOccurrences[$fc->field_name] ?? 0) + 1;
                        }
                    }
                }
            }

            // 2. From ValidationHistory on items and purchaseRequest
            $valHistories = collect();
            if ($pr->relationLoaded('items')) {
                foreach ($pr->items as $item) {
                    if ($item->relationLoaded('validationHistories')) {
                        $valHistories = $valHistories->concat($item->validationHistories->where('status', 'FAIL'));
                    }
                }
            }
            if ($pr->relationLoaded('validationHistories')) {
                $valHistories = $valHistories->concat($pr->validationHistories->where('status', 'FAIL'));
            }

            $valHistoryCounts = [];
            foreach ($valHistories as $vh) {
                $fName = $vh->rule ? ($vh->rule->label ?: $vh->rule->field_name) : null;
                if ($fName) {
                    if ($this->isFailureMatchingTolerance($fName, $vh->expected_value, $vh->input_value)) {
                        $valHistoryCounts[$fName] = ($valHistoryCounts[$fName] ?? 0) + 1;
                    }
                }
            }

            // Canonicalize occurrences for this specific purchase request
            $canonicalFieldOccurrences = [];
            foreach ($fieldOccurrences as $f => $cnt) {
                $cKey = self::getCanonicalFieldInfo($f)['key'];
                $canonicalFieldOccurrences[$cKey] = max($canonicalFieldOccurrences[$cKey] ?? 0, $cnt);
            }

            $canonicalValHistoryCounts = [];
            foreach ($valHistoryCounts as $f => $cnt) {
                $cKey = self::getCanonicalFieldInfo($f)['key'];
                $canonicalValHistoryCounts[$cKey] = max($canonicalValHistoryCounts[$cKey] ?? 0, $cnt);
            }

            $allCanonKeys = array_unique(array_merge(
                array_keys($canonicalFieldOccurrences),
                array_keys($canonicalValHistoryCounts)
            ));

            if (empty($allCanonKeys)) {
                continue;
            }

            $allDistinctRepurchaseIds[$repurchaseId] = true;

            foreach ($allCanonKeys as $canonKey) {
                $fcCount = $canonicalFieldOccurrences[$canonKey] ?? 0;
                $vhCount = $canonicalValHistoryCounts[$canonKey] ?? 0;
                $occCount = max($fcCount, $vhCount, 1);
                $canonLabel = self::formatFieldLabel($canonKey);

                // Table 1 data accumulation
                if (! isset($table1Data[$canonKey])) {
                    $table1Data[$canonKey] = [
                        'field_name' => $canonKey,
                        'label' => $canonLabel,
                        'wrong_field_count' => 0,
                        'distinct_repurchase_ids' => [],
                        'distinct_repurchase_count' => 0,
                        'open_count' => 0,
                    ];
                }

                $table1Data[$canonKey]['wrong_field_count'] += $occCount;
                $table1Data[$canonKey]['distinct_repurchase_ids'][$repurchaseId] = true;
                if ($isOpen) {
                    $table1Data[$canonKey]['open_count']++;
                }

                // Table 2 data accumulation (grouped by canonical field, then branch)
                if (! isset($table2Data[$canonKey])) {
                    $table2Data[$canonKey] = [
                        'field_name' => $canonKey,
                        'label' => $canonLabel,
                        'total_wrong_count' => 0,
                        'distinct_repurchase_ids' => [],
                        'distinct_repurchase_count' => 0,
                        'branches' => [],
                    ];
                }

                $table2Data[$canonKey]['total_wrong_count'] += $occCount;
                $table2Data[$canonKey]['distinct_repurchase_ids'][$repurchaseId] = true;

                if (! isset($table2Data[$canonKey]['branches'][$branchName])) {
                    $table2Data[$canonKey]['branches'][$branchName] = [
                        'wrong_count' => 0,
                        'distinct_repurchase_ids' => [],
                        'distinct_repurchase_count' => 0,
                    ];
                }

                $table2Data[$canonKey]['branches'][$branchName]['wrong_count'] += $occCount;
                $table2Data[$canonKey]['branches'][$branchName]['distinct_repurchase_ids'][$repurchaseId] = true;
            }
        }

        // Finalize Table 1 distinct counts and totals
        foreach ($table1Data as $fieldName => $item) {
            $table1Data[$fieldName]['distinct_repurchase_count'] = count($item['distinct_repurchase_ids']);
            $totalWrongFieldCount += $item['wrong_field_count'];
            $totalOpenDecisionsCount += $item['open_count'];
        }

        // Sort Table 1 by distinct repurchase count descending
        uasort($table1Data, fn ($a, $b) => $b['distinct_repurchase_count'] <=> $a['distinct_repurchase_count']);

        // Finalize Table 2 distinct counts and sorting
        foreach ($table2Data as $fieldName => $group) {
            $table2Data[$fieldName]['distinct_repurchase_count'] = count($group['distinct_repurchase_ids']);

            foreach ($group['branches'] as $branchName => $bData) {
                $table2Data[$fieldName]['branches'][$branchName]['distinct_repurchase_count'] = count($bData['distinct_repurchase_ids']);
            }

            uasort(
                $table2Data[$fieldName]['branches'],
                fn ($a, $b) => $b['distinct_repurchase_count'] <=> $a['distinct_repurchase_count']
            );
        }

        // Sort Table 2 fields by distinct repurchase count descending
        uasort($table2Data, fn ($a, $b) => $b['distinct_repurchase_count'] <=> $a['distinct_repurchase_count']);

        // Formatted dates for display
        $formattedStartDate = $this->startDate ? Carbon::parse($this->startDate)->format('d M Y') : 'အစအဦးမှ (Beginning)';
        $formattedEndDate = $this->endDate ? Carbon::parse($this->endDate)->format('d M Y') : 'ယနေ့အထိ (Present)';

        $toleranceInfo = null;
        if ($this->isToleranceFilterActive()) {
            $toleranceInfo = [
                'isActive' => true,
                'field' => $this->toleranceField,
                'fieldLabel' => self::formatFieldLabel($this->toleranceField ?? 'all'),
                'value' => (float) $this->toleranceValue,
                'mode' => $this->toleranceMode,
                'modeLabel' => $this->toleranceMode === 'excluded'
                    ? 'ကင်းလွတ်ခွင့်ပြုထားသည် (Excluded within tolerance)'
                    : 'ရွေးထုတ်ထားသည် (Retrieved within tolerance)',
            ];
        }

        $stateInfo = [
            'value' => $this->selectedState,
            'label' => $this->getStateLabel(),
            'isFiltered' => $this->selectedState !== 'all',
        ];

        return [
            'companyTitle' => $this->getCompanyTitle(),
            'decisionsCount' => $decisions->count(),
            'table1' => array_values($table1Data),
            'table2' => array_values($table2Data),
            'totalDecisionsCount' => $totalWrongFieldCount,
            'totalWrongFieldCount' => $totalWrongFieldCount,
            'totalDistinctRepurchases' => count($allDistinctRepurchaseIds),
            'totalOpenDecisionsCount' => $totalOpenDecisionsCount,
            'formattedStartDate' => $formattedStartDate,
            'formattedEndDate' => $formattedEndDate,
            'toleranceInfo' => $toleranceInfo,
            'stateInfo' => $stateInfo,
        ];
    }

    public function getCompanyTitle(): string
    {
        if (! empty($this->companyTitle)) {
            return $this->companyTitle;
        }

        if ($custom = config('app.company_name')) {
            return strtoupper($custom);
        }

        $appName = config('app.name');
        if ($appName && ! in_array(strtolower($appName), ['laravel', 'portal', 'protal'])) {
            return strtoupper($appName . ' - REPURCHASE');
        }

        $user = auth()->user();
        if ($user?->branch?->name && ! $user->hasRole(['super-admin', 'Super Admin'])) {
            return strtoupper("MAHAR ({$user->branch->name}) - REPURCHASE");
        }

        return 'MAHAR JEWELRY & GOLD REPURCHASE';
    }

    public function exportPdf(): StreamedResponse
    {
        $reportData = $this->getReportData();

        $pdf = Pdf::loadView('filament.repurchase.pages.decision-report-pdf', [
            'reportData' => $reportData,
            'table1' => $reportData['table1'],
            'table2' => $reportData['table2'],
            'totalWrongFieldCount' => $reportData['totalWrongFieldCount'],
            'totalDistinctRepurchases' => $reportData['totalDistinctRepurchases'],
            'totalOpen' => $reportData['totalOpenDecisionsCount'],
            'startDateText' => $reportData['formattedStartDate'],
            'endDateText' => $reportData['formattedEndDate'],
            'companyTitle' => $reportData['companyTitle'],
            'toleranceInfo' => $reportData['toleranceInfo'],
            'stateInfo' => $reportData['stateInfo'],
        ])
        ->setPaper('a4', 'portrait')
        ->setOption('isHtml5ParserEnabled', true)
        ->setOption('isRemoteEnabled', true);

        $filename = 'Repurchase-Performance-Analysis-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
