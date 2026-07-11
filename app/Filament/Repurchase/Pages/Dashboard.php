<?php

namespace App\Filament\Repurchase\Pages;

use App\Filament\Repurchase\Widgets\RepurchaseStatsWidget;
use App\Filament\Repurchase\Widgets\WorkflowRequestsChartWidget;
use App\Filament\Repurchase\Widgets\GoldGradeRepurchaseChartWidget;
use App\Filament\Repurchase\Widgets\GoldGradeRepurchaseTableWidget;
use App\Filament\Repurchase\Widgets\FailedValidationFieldsChartWidget;
use App\Filament\Repurchase\Widgets\PurchaseDecisionStatusChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            RepurchaseStatsWidget::class,
            WorkflowRequestsChartWidget::class,
            GoldGradeRepurchaseChartWidget::class,
            GoldGradeRepurchaseTableWidget::class,
            FailedValidationFieldsChartWidget::class,
            PurchaseDecisionStatusChartWidget::class,
        ];
    }
}
