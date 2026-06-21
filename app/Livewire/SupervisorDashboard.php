<?php

namespace App\Livewire;

use App\Models\Decision;
use App\Models\ProductCheck;
use Livewire\Component;

class SupervisorDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Supervisor']), 403);
    }

    public function render()
    {
        return view('livewire.supervisor-dashboard', [
            'openDecisions' => Decision::with(['decisionType', 'assignedTo', 'decisionBy', 'productCheck.product'])
                ->where('action_status', 'OPEN')
                ->latest()
                ->limit(10)
                ->get(),
            'inProgressDecisions' => Decision::where('action_status', 'IN_PROGRESS')->count(),
            'openDecisionCount' => Decision::where('action_status', 'OPEN')->count(),
            'failedCheckCount' => ProductCheck::where('result_status', 'FAIL')->count(),
            'warningCheckCount' => ProductCheck::where('result_status', 'WARNING')->count(),
        ])->layout('layouts.app');
    }
}
