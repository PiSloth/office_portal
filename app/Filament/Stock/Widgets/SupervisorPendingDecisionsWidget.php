<?php

namespace App\Filament\Stock\Widgets;

use App\Models\Decision;
use App\Models\ProductCheck;
use Filament\Widgets\Widget;

class SupervisorPendingDecisionsWidget extends Widget
{
    protected ?string $pollingInterval = '5s';

    protected string $view = 'dashboard-supervisor-pending-decisions';

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'pendingDecisions' => Decision::where('action_status', 'OPEN')->count(),
            'inProgressDecisions' => Decision::where('action_status', 'IN_PROGRESS')->count(),
            'failedChecks' => ProductCheck::where('result_status', 'FAIL')->count(),
        ];
    }
}
