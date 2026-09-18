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

        $totalDecisionsCount = 0;
        $totalOpenDecisionsCount = 0;

        foreach ($decisions as $decision) {
            $pr = $decision->purchaseRequest;
            $failChecks = $pr?->failChecks ?? collect();
            $isOpen = $decision->status === 'open';

            $branchName = $pr?->branch?->name ?: ($pr?->branch?->code ?: 'သတ်မှတ်မထားသော ဌာနခွဲ (Unassigned Branch)');

            // Get unique field names for this decision to avoid double counting same field on multiple items of same decision
            $failedFields = $failChecks->pluck('field_name')->filter()->unique();

            if ($failedFields->isEmpty()) {
                $failedFields = collect(['other_unspecified']);
            }

            foreach ($failedFields as $fieldName) {
                // Table 1 data accumulation
                if (! isset($table1Data[$fieldName])) {
                    $table1Data[$fieldName] = [
                        'field_name' => $fieldName,
                        'label' => self::formatFieldLabel($fieldName),
                        'decision_count' => 0,
                        'open_count' => 0,
                    ];
                }

                $table1Data[$fieldName]['decision_count']++;
                if ($isOpen) {
                    $table1Data[$fieldName]['open_count']++;
                }

                // Table 2 data accumulation (grouped by field, then branch)
                if (! isset($table2Data[$fieldName])) {
                    $table2Data[$fieldName] = [
                        'field_name' => $fieldName,
                        'label' => self::formatFieldLabel($fieldName),
                        'total_count' => 0,
                        'branches' => [],
                    ];
                }

                $table2Data[$fieldName]['total_count']++;
                $table2Data[$fieldName]['branches'][$branchName] = ($table2Data[$fieldName]['branches'][$branchName] ?? 0) + 1;
            }
        }

        // Sort Table 1 by decision count descending
        uasort($table1Data, fn ($a, $b) => $b['decision_count'] <=> $a['decision_count']);

        // Calculate totals for Table 1
        foreach ($table1Data as $item) {
            $totalDecisionsCount += $item['decision_count'];
            $totalOpenDecisionsCount += $item['open_count'];
        }

        // Sort Table 2 fields by total count descending, and their branches by count descending
        uasort($table2Data, fn ($a, $b) => $b['total_count'] <=> $a['total_count']);

        foreach ($table2Data as $fieldName => $data) {
            arsort($data['branches']);
            $table2Data[$fieldName]['branches'] = $data['branches'];
        }

        // Formatted dates for display
        $formattedStartDate = $this->startDate ? Carbon::parse($this->startDate)->format('d M Y') : 'အစအဦးမှ (Beginning)';
        $formattedEndDate = $this->endDate ? Carbon::parse($this->endDate)->format('d M Y') : 'ယနေ့အထိ (Present)';

        return [
            'companyTitle' => $this->getCompanyTitle(),
            'decisionsCount' => $decisions->count(),
            'table1' => array_values($table1Data),
            'table2' => array_values($table2Data),
            'totalDecisionsCount' => $totalDecisionsCount,
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
            'totalDecisions' => $reportData['totalDecisionsCount'],
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
