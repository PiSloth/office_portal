<?php

namespace App\Filament\Repurchase\Pages;

use App\Filament\Repurchase\Widgets\RepurchaseStatsWidget;
use App\Filament\Repurchase\Widgets\WorkflowRequestsChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            RepurchaseStatsWidget::class,
            WorkflowRequestsChartWidget::class,
        ];
    }
}
