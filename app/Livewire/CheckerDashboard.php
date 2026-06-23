<?php

namespace App\Livewire;

use App\Models\CheckSession;
use App\Models\Decision;
use App\Models\ProductCheck;
use App\Models\ScanConfig;
use Livewire\Component;

class CheckerDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['super-admin', 'admin', 'manager', 'checker', 'Super Admin', 'Admin', 'Supervisor', 'Checker']), 403);
    }

    public function render()
    {
        return view('livewire.checker-dashboard', [
            'sessionCount' => CheckSession::whereIn('status', ['DRAFT', 'OPEN'])->count(),
            'scanConfigCount' => ScanConfig::where('is_active', true)->count(),
            'todayChecks' => ProductCheck::whereDate('created_at', now()->toDateString())->count(),
            'failedChecks' => ProductCheck::where('result_status', 'FAIL')->count(),
            'recentSessions' => CheckSession::with('startedBy')
                ->latest('started_at')
                ->limit(5)
                ->get(),
            'openDecisions' => Decision::with(['decisionType', 'assignedTo', 'productCheck.product'])
                ->where('action_status', 'OPEN')
                ->latest()
                ->limit(5)
                ->get(),
        ])->layout('layouts.app');
    }
}
