<?php

namespace App\Filament\Widgets;

use App\Models\CheckSession;
use App\Models\ProductCheck;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Carbon;

class UserCheckedProductsLineChartWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Checked Products by User';

    protected int | string | array $columnSpan = 2;

    protected bool $hasDeferredFilters = true;

    protected function getType(): string
    {
        return 'line';
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('session_id')
                ->label('Session')
                ->options([
                    'all' => 'All sessions',
                    ...CheckSession::query()
                        ->orderByDesc('started_at')
                        ->pluck('name', 'id')
                        ->all(),
                ])
                ->default('all')
                ->searchable(),
            DatePicker::make('start_date')
                ->label('Start Date')
                ->default(now()->startOfMonth()),
            DatePicker::make('end_date')
                ->label('End Date')
                ->default(now()),
        ])->columns(3);
    }

    protected function getData(): array
    {
        $startDate = Carbon::parse($this->filters['start_date'] ?? now()->startOfMonth())->startOfDay();
        $endDate = Carbon::parse($this->filters['end_date'] ?? now())->endOfDay();
        $sessionId = $this->filters['session_id'] ?? 'all';

        $query = ProductCheck::query()
            ->with(['checkedBy'])
            ->whereBetween('checked_at', [$startDate, $endDate]);

        if ($sessionId !== 'all') {
            $query->where('check_session_id', $sessionId);
        }

        $records = $query->get();
        $userCounts = $records
            ->groupBy(fn (ProductCheck $record): string => $record->checkedBy?->name ?? 'Unknown')
            ->map(fn ($items) => $items->count())
            ->sortDesc();
        $userNames = $userCounts->keys()->values();
        $userTotals = $userCounts->values()->all();

        $palette = ['#7531bc', '#fc950f', '#2AEFC8', '#5d2596', '#22c55e', '#ef4444', '#0ea5e9', '#eab308'];

        return [
            'datasets' => [
                [
                    'label' => 'Checked Products',
                    'data' => $userTotals,
                    'borderColor' => $palette[0],
                    'backgroundColor' => $palette[0],
                    'tension' => 0.35,
                ],
            ],
            'labels' => $userNames->all(),
        ];
    }
}
