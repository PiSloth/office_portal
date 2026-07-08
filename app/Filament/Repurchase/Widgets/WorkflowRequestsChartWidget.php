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

    protected int | string | array $columnSpan = 'full';

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
                    ...Branch::pluck('name', 'id')->all(),
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
        foreach ($states as $state) {
            $labels[] = $state->name;
            $data[] = $counts[$state->id] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Requests Count',
                    'data' => $data,
                    'backgroundColor' => '#2aeFC8',
                    'borderColor' => '#0ea5e9',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
