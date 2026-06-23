<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminStatsWidget;
use App\Filament\Widgets\DelayedDecisionStatusChartWidget;
use App\Filament\Widgets\ProductTypeCheckedPieChartWidget;
use App\Filament\Widgets\OpenDecisionsWidget;
use App\Filament\Widgets\SessionResultStackedColumnChartWidget;
use App\Filament\Widgets\RecentProductChecksWidget;
use App\Filament\Widgets\RecentProductImportsWidget;
use App\Filament\Widgets\UserCheckedProductsLineChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            AdminStatsWidget::class,
            ProductTypeCheckedPieChartWidget::class,
            UserCheckedProductsLineChartWidget::class,
            SessionResultStackedColumnChartWidget::class,
            DelayedDecisionStatusChartWidget::class,
            RecentProductChecksWidget::class,
            OpenDecisionsWidget::class,
            RecentProductImportsWidget::class,
        ];
    }
}
