<?php

namespace App\Filament\Widgets;

use App\Models\CheckSession;
use App\Models\Decision;
use App\Models\Product;
use App\Models\ScanConfig;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Products', Product::count())
                ->icon('heroicon-o-cube')
                ->color('primary'),
            Stat::make('Scan Configs', ScanConfig::where('is_active', true)->count())
                ->icon('heroicon-o-qr-code')
                ->color('info'),
            Stat::make('Open Decisions', Decision::where('action_status', 'OPEN')->count())
                ->icon('heroicon-o-flag')
                ->color('warning'),
            Stat::make('Active Sessions', CheckSession::whereIn('status', ['DRAFT', 'OPEN'])->count())
                ->icon('heroicon-o-bolt')
                ->color('success'),
        ];
    }
}
