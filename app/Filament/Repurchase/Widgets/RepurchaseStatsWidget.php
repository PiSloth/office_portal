<?php

namespace App\Filament\Repurchase\Widgets;

use App\Modules\Purchase\Models\PurchaseRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RepurchaseStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Purchase Requests', PurchaseRequest::count())
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),
            Stat::make('Total Purchase Amount', 'MMK ' . number_format(PurchaseRequest::sum('total_amount'), 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Draft / Pending Requests', PurchaseRequest::whereHas('workflowState', function ($query) {
                $query->where('is_start', true)->orWhere('is_end', false);
            })->count())
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
