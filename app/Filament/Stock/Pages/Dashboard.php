<?php

namespace App\Filament\Stock\Pages;

use App\Filament\Stock\Widgets\AdminStatsWidget;
use App\Filament\Stock\Widgets\DelayedDecisionStatusChartWidget;
use App\Filament\Stock\Widgets\ProductTypeCheckedPieChartWidget;
use App\Filament\Stock\Widgets\ProductImportVsCheckedPieChartWidget;
use App\Filament\Stock\Widgets\OpenDecisionsWidget;
use App\Filament\Stock\Widgets\SessionResultStackedColumnChartWidget;
use App\Filament\Stock\Widgets\RecentProductChecksWidget;
use App\Filament\Stock\Widgets\RecentProductImportsWidget;
use App\Filament\Stock\Widgets\UserCheckedProductsLineChartWidget;
use App\Filament\Stock\Widgets\CategoryCheckReportWidget;
use App\Filament\Stock\Widgets\PickupAndUnmatchedSummaryWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            AdminStatsWidget::class,
            PickupAndUnmatchedSummaryWidget::class,
            ProductImportVsCheckedPieChartWidget::class,
            ProductTypeCheckedPieChartWidget::class,
            UserCheckedProductsLineChartWidget::class,
            SessionResultStackedColumnChartWidget::class,
            DelayedDecisionStatusChartWidget::class,
            RecentProductChecksWidget::class,
            OpenDecisionsWidget::class,
            RecentProductImportsWidget::class,
            CategoryCheckReportWidget::class,
        ];
    }
}
