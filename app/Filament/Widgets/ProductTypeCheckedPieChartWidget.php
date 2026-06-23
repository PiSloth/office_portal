<?php

namespace App\Filament\Widgets;

use App\Models\ProductCheck;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class ProductTypeCheckedPieChartWidget extends ChartWidget
{
    protected ?string $heading = 'Checked Product Types';

    public ?string $filter = null;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '220px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getFilters(): ?array
    {
        return $this->getMonthFilters();
    }

    protected function getData(): array
    {
        $month = $this->resolveSelectedMonth();

        $counts = ProductCheck::query()
            ->with('product.productType')
            ->whereBetween('checked_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()
            ->groupBy(fn (ProductCheck $record): string => $record->product?->productType?->name ?? 'Uncategorized')
            ->map(fn (Collection $items): int => $items->count())
            ->sortDesc();

        return [
            'datasets' => [
                [
                    'data' => $counts->values()->all(),
                    'backgroundColor' => [
                        '#7531bc',
                        '#fc950f',
                        '#2AEFC8',
                        '#5d2596',
                        '#22c55e',
                        '#ef4444',
                        '#0ea5e9',
                        '#eab308',
                    ],
                ],
            ],
            'labels' => $counts->keys()->all(),
        ];
    }

    private function getMonthFilters(): array
    {
        return collect(range(0, 11))
            ->mapWithKeys(fn (int $offset): array => [
                now()->subMonthsNoOverflow($offset)->format('Y-m') => now()
                    ->subMonthsNoOverflow($offset)
                    ->translatedFormat('F Y'),
            ])
            ->all();
    }

    private function resolveSelectedMonth(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->filter ?? now()->format('Y-m'))->startOfMonth();
    }
}
