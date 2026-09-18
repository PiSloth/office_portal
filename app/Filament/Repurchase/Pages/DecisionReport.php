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

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->companyTitle = $this->getCompanyTitle();
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

    public static function formatFieldLabel(string $fieldName): string
    {
        $labels = [
            'weight_gram' => 'အလေးချိန် (Weight Gram)',
            'weight_g' => 'အလေးချိန် (Weight)',
            'gold_grade' => 'ရွှေရည် (Gold Grade)',
            'gold_quality' => 'ရွှေအရည်အသွေး (Gold Quality)',
            'expiry_date' => 'သက်တမ်းကုန်ဆုံးရက် (Expiry Date)',
            'imei' => 'IMEI နံပါတ်',
        ];

        return $labels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
    }

    public function getReportData(): array
    {
        $query = PurchaseDecision::with([
            'purchaseRequest.branch',
            'purchaseRequest.failChecks',
            'purchaseRequest.items.validationHistories.rule',
            'purchaseRequest.validationHistories.rule',
        ])
            ->whereHas('purchaseRequest', function ($q) {
                $q->whereNull('deleted_at');
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
            $allDistinctRepurchaseIds[$repurchaseId] = true;

            $branchName = $pr->branch?->name ?: ($pr->branch?->code ?: 'သတ်မှတ်မထားသော ဌာနခွဲ (Unassigned Branch)');
            $isOpen = $decision->status === 'open';

            // Collect all wrong field occurrences on this specific purchase request
            $fieldOccurrences = [];

            // 1. From failChecks
            if ($pr->relationLoaded('failChecks')) {
                foreach ($pr->failChecks as $fc) {
                    if (! empty($fc->field_name)) {
                        $fieldOccurrences[$fc->field_name] = ($fieldOccurrences[$fc->field_name] ?? 0) + 1;
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
                    $valHistoryCounts[$fName] = ($valHistoryCounts[$fName] ?? 0) + 1;
                }
            }

            // Combine both sources, taking the maximum count per field to ensure all triggers are captured
            $allFields = array_unique(array_merge(array_keys($fieldOccurrences), array_keys($valHistoryCounts)));

            if (empty($allFields)) {
                $allFields = ['other_unspecified'];
                $fieldOccurrences['other_unspecified'] = 1;
            } else {
                foreach ($allFields as $f) {
                    $fcCount = $fieldOccurrences[$f] ?? 0;
                    $vhCount = $valHistoryCounts[$f] ?? 0;
                    $fieldOccurrences[$f] = max($fcCount, $vhCount, 1);
                }
            }

            foreach ($allFields as $fieldName) {
                $occCount = $fieldOccurrences[$fieldName];

                // Table 1 data accumulation
                if (! isset($table1Data[$fieldName])) {
                    $table1Data[$fieldName] = [
                        'field_name' => $fieldName,
                        'label' => self::formatFieldLabel($fieldName),
                        'wrong_field_count' => 0,
                        'distinct_repurchase_ids' => [],
                        'distinct_repurchase_count' => 0,
                        'open_count' => 0,
                    ];
                }

                $table1Data[$fieldName]['wrong_field_count'] += $occCount;
                $table1Data[$fieldName]['distinct_repurchase_ids'][$repurchaseId] = true;
                if ($isOpen) {
                    $table1Data[$fieldName]['open_count']++;
                }

                // Table 2 data accumulation (grouped by field, then branch)
                if (! isset($table2Data[$fieldName])) {
                    $table2Data[$fieldName] = [
                        'field_name' => $fieldName,
                        'label' => self::formatFieldLabel($fieldName),
                        'total_wrong_count' => 0,
                        'distinct_repurchase_ids' => [],
                        'distinct_repurchase_count' => 0,
                        'branches' => [],
                    ];
                }

                $table2Data[$fieldName]['total_wrong_count'] += $occCount;
                $table2Data[$fieldName]['distinct_repurchase_ids'][$repurchaseId] = true;

                if (! isset($table2Data[$fieldName]['branches'][$branchName])) {
                    $table2Data[$fieldName]['branches'][$branchName] = [
                        'wrong_count' => 0,
                        'distinct_repurchase_ids' => [],
                        'distinct_repurchase_count' => 0,
                    ];
                }

                $table2Data[$fieldName]['branches'][$branchName]['wrong_count'] += $occCount;
                $table2Data[$fieldName]['branches'][$branchName]['distinct_repurchase_ids'][$repurchaseId] = true;
            }
        }

        // Finalize Table 1 distinct counts and totals
        foreach ($table1Data as $fieldName => $item) {
            $table1Data[$fieldName]['distinct_repurchase_count'] = count($item['distinct_repurchase_ids']);
            $totalWrongFieldCount += $item['wrong_field_count'];
            $totalOpenDecisionsCount += $item['open_count'];
        }

        // Sort Table 1 by wrong field count descending, then distinct repurchase count
        uasort($table1Data, fn ($a, $b) => ($b['wrong_field_count'] <=> $a['wrong_field_count']) ?: ($b['distinct_repurchase_count'] <=> $a['distinct_repurchase_count']));

        // Finalize Table 2 distinct counts and sorting
        foreach ($table2Data as $fieldName => $group) {
            $table2Data[$fieldName]['distinct_repurchase_count'] = count($group['distinct_repurchase_ids']);

            foreach ($group['branches'] as $branchName => $bData) {
                $table2Data[$fieldName]['branches'][$branchName]['distinct_repurchase_count'] = count($bData['distinct_repurchase_ids']);
            }

            uasort(
                $table2Data[$fieldName]['branches'],
                fn ($a, $b) => ($b['wrong_count'] <=> $a['wrong_count']) ?: ($b['distinct_repurchase_count'] <=> $a['distinct_repurchase_count'])
            );
        }

        // Sort Table 2 fields by total wrong count descending, then distinct repurchase count
        uasort($table2Data, fn ($a, $b) => ($b['total_wrong_count'] <=> $a['total_wrong_count']) ?: ($b['distinct_repurchase_count'] <=> $a['distinct_repurchase_count']));

        // Formatted dates for display
        $formattedStartDate = $this->startDate ? Carbon::parse($this->startDate)->format('d M Y') : 'အစအဦးမှ (Beginning)';
        $formattedEndDate = $this->endDate ? Carbon::parse($this->endDate)->format('d M Y') : 'ယနေ့အထိ (Present)';

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
