<?php

namespace App\Filament\Widgets;

use App\Models\ProductCheck;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SessionResultStackedColumnChartWidget extends ChartWidget
{
    protected ?string $heading = 'Check Results by Session';

    public ?string $filter = null;

    protected int | string | array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return collect(range(0, 11))
            ->mapWithKeys(fn (int $offset): array => [
                now()->subMonthsNoOverflow($offset)->format('Y-m') => now()
                    ->subMonthsNoOverflow($offset)
                    ->translatedFormat('F Y'),
            ])
            ->all();
    }

    protected function getData(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->filter ?? now()->format('Y-m'))->startOfMonth();

        $records = ProductCheck::query()
            ->with('checkSession')
            ->whereBetween('checked_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        $labels = $records
            ->map(fn (ProductCheck $record): string => $record->checkSession?->name ?? 'Unknown Session')
            ->unique()
            ->values();

        $statuses = ['PASS', 'FAIL', 'WARNING'];
        $statusLabels = [
            'PASS' => 'Pass',
            'FAIL' => 'Fail',
            'WARNING' => 'Warning',
        ];
        $statusColors = [
            'PASS' => '#22c55e',
            'FAIL' => '#ef4444',
            'WARNING' => '#f59e0b',
        ];

        $datasets = [];
        foreach ($statuses as $status) {
            $datasets[] = [
                'label' => $statusLabels[$status],
                'data' => $labels->map(fn (string $sessionName): int => $records
                    ->filter(fn (ProductCheck $record): bool => ($record->checkSession?->name ?? 'Unknown Session') === $sessionName && $record->result_status === $status)
                    ->count())->all(),
                'backgroundColor' => $statusColors[$status],
                'stack' => 'results',
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
