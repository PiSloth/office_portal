<?php

namespace App\Filament\Repurchase\Widgets;

use App\Models\Branch;
use App\Models\ProductType;
use App\Modules\Purchase\Models\PurchaseRequest;
use App\Modules\Core\Workflow\Models\WorkflowState;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class WorkflowRequestsChartWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Purchase Requests by Workflow State';

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
            Select::make('product_type_id')
                ->label('Product Type')
                ->options([
                    'all' => 'All Product Types',
                    ...ProductType::pluck('name', 'id')->all(),
                ])
                ->default('all')
                ->searchable(),
        ])->columns(2);
    }

    protected function getData(): array
    {
        $branchId = $this->filters['branch_id'] ?? 'all';
        $productTypeId = $this->filters['product_type_id'] ?? 'all';

        // Get workflow states that are not end states
        $states = WorkflowState::where('is_end', false)
            ->orWhereNull('is_end')
            ->get();

        $query = PurchaseRequest::query();

        if ($branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($productTypeId !== 'all') {
            $query->where('product_type_id', $productTypeId);
        }

        $counts = $query->selectRaw('workflow_state_id, count(*) as count')
            ->groupBy('workflow_state_id')
            ->pluck('count', 'workflow_state_id')
            ->all();

        $labels = [];
        $data = [];
        $backgroundColors = [];
        $palette = ['#7531bc', '#fc950f', '#2AEFC8', '#22c55e', '#ef4444', '#0ea5e9', '#eab308', '#ec4899', '#8b5cf6', '#6b7280'];

        foreach ($states as $index => $state) {
            $labels[] = $state->name;
            $data[] = $counts[$state->id] ?? 0;
            $backgroundColors[] = $state->color ?: $palette[$index % count($palette)];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Requests Count',
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
