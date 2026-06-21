<?php

namespace App\Filament\Widgets;

use App\Models\CheckSession;
use App\Models\Decision;
use App\Models\Product;
use App\Models\ScanConfig;
use Filament\Widgets\Widget;

class AdminStatsWidget extends Widget
{
    protected string $view = 'dashboard-admin-stats';

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'productCount' => Product::count(),
            'scanConfigCount' => ScanConfig::where('is_active', true)->count(),
            'openDecisionCount' => Decision::where('action_status', 'OPEN')->count(),
            'activeSessionCount' => CheckSession::whereIn('status', ['DRAFT', 'OPEN'])->count(),
        ];
    }
}
