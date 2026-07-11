<?php

namespace App\Filament\Repurchase\Widgets;

use App\Models\Branch;
use App\Modules\Purchase\Models\PurchaseDecision;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class PurchaseDecisionStatusChartWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Purchase Decision Aging & Resolution';

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_id')
                ->label('Branch')
                ->options([
                    'all' => 'All Branches',
                    ...Branch::pluck('code', 'id')->all(),
                ])
                ->default('all')
                ->searchable(),
        ]);
    }

    protected function getData(): array
    {
        $branchId = $this->filters['branch_id'] ?? 'all';

        $query = PurchaseDecision::query();

        if ($branchId && $branchId !== 'all') {
            $query->whereHas('purchaseRequest', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $decisions = $query->get();

        $todayCount = 0;
        $twoDaysCount = 0;
        $threeDaysCount = 0;
        $fourDaysCount = 0;
        $fiveDaysPlusCount = 0;
        $closedLast7DaysCount = 0;

        foreach ($decisions as $decision) {
            if ($decision->status === 'closed') {
                if ($decision->updated_at >= now()->subDays(7)) {
                    $closedLast7DaysCount++;
                }
            } else {
                $days = (int) now()->diffInDays($decision->created_at);
                if ($days === 0) {
                    $todayCount++;
                } elseif ($days === 1) {
                    $twoDaysCount++;
                } elseif ($days === 2) {
                    $threeDaysCount++;
                } elseif ($days === 3) {
                    $fourDaysCount++;
                } else {
                    $fiveDaysPlusCount++;
                }
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Decisions Count',
                    'data' => [
                        $todayCount,
                        $twoDaysCount,
                        $threeDaysCount,
                        $fourDaysCount,
                        $fiveDaysPlusCount,
                        $closedLast7DaysCount,
                    ],
                    'backgroundColor' => [
                        '#fb7185', // Today
                        '#f43f5e', // 2 Days
                        '#e11d48', // 3 Days
                        '#be123c', // 4 Days
                        '#9f1239', // 5 Days+
                        '#10b981', // Closed last 7 days (green)
                    ],
                ],
            ],
            'labels' => [
                'Today (Open)',
                '2 Days (Open)',
                '3 Days (Open)',
                '4 Days (Open)',
                '5 Days+ (Open)',
                'Closed (Last 7 Days)',
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
