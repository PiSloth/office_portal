<?php

namespace App\Filament\Repurchase\Widgets;

use App\Models\Branch;
use App\Modules\Purchase\Models\FailCheck;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class FailedValidationFieldsChartWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Failed Checks by Field Name';

    protected int | string | array $columnSpan = 'full';

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

        $query = FailCheck::query();

        if ($branchId && $branchId !== 'all') {
            $query->whereHas('purchaseRequest', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $counts = $query->selectRaw('field_name, count(*) as count')
            ->groupBy('field_name')
            ->orderByDesc('count')
            ->pluck('count', 'field_name')
            ->all();

        $labels = array_keys($counts);
        $data = array_values($counts);
        
        $palette = ['#f43f5e', '#fb7185', '#fda4af', '#fecdd3', '#ffe4e6'];
        $backgroundColors = [];
        foreach ($labels as $index => $label) {
            $backgroundColors[] = $palette[$index % count($palette)];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Fail Count',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors,
                ],
            ],
            'labels' => $labels,
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
