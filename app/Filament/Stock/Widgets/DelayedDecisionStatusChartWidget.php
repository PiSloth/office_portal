<?php

namespace App\Filament\Stock\Widgets;

use App\Models\Decision;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class DelayedDecisionStatusChartWidget extends ChartWidget
{
    protected ?string $heading = 'Delayed Decision Status';

    protected int | string | array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $records = Decision::query()
            ->whereIn('action_status', ['OPEN', 'IN_PROGRESS'])
            ->whereDate('created_at', '<=', now()->toDateString())
            ->get();

        $buckets = collect([
            '0 days' => fn (int $days): bool => $days === 0,
            '1 day' => fn (int $days): bool => $days === 1,
            '2 days' => fn (int $days): bool => $days === 2,
            '3 days' => fn (int $days): bool => $days === 3,
            '4+ days' => fn (int $days): bool => $days >= 4,
        ]);

        $labels = $buckets->keys()->values();
        $statuses = ['OPEN', 'IN_PROGRESS'];
        $statusLabels = [
            'OPEN' => 'Open',
            'IN_PROGRESS' => 'Pending',
        ];
        $statusColors = [
            'OPEN' => '#fc950f',
            'IN_PROGRESS' => '#7531bc',
        ];

        $datasets = [];
        foreach ($statuses as $status) {
            $datasets[] = [
                'label' => $statusLabels[$status],
                'data' => $labels->map(function (string $bucketLabel) use ($records, $buckets, $status): int {
                    $matcher = $buckets[$bucketLabel];

                    return $records
                        ->filter(function (Decision $decision) use ($matcher, $status): bool {
                            if ($decision->action_status !== $status) {
                                return false;
                            }

                            $daysDelayed = Carbon::parse($decision->created_at)->startOfDay()->diffInDays(now()->startOfDay());

                            return $matcher($daysDelayed);
                        })
                        ->count();
                })->all(),
                'backgroundColor' => $statusColors[$status],
                'stack' => 'delayed',
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
                    'stacked' => false,
                ],
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
