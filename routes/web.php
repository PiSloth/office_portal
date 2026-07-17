<?php

use App\Livewire\CheckerDashboard;
use App\Livewire\MobileScanner;
use App\Livewire\SupervisorDashboard;
use App\Http\Controllers\ProductImportTemplateController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

use Illuminate\Http\Request;

Route::view('/', 'welcome');

Route::get('/manual', function () {
    $path = public_path('manual.md');
    if (!file_exists($path)) {
        abort(404, 'Manual file not found.');
    }
    $markdown = file_get_contents($path);
    $html = Str::markdown($markdown);
    return view('manual', compact('html'));
})->name('manual');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/manual/edit', function () {
        $path = public_path('manual.md');
        $content = file_exists($path) ? file_get_contents($path) : '';
        return view('manual-edit', compact('content'));
    })->name('manual.edit');

    Route::post('/manual', function (Request $request) {
        $path = public_path('manual.md');
        file_put_contents($path, $request->input('content', ''));
        return redirect()->route('manual')->with('success', 'Manual updated successfully.');
    })->name('manual.update');

    Route::post('/manual/import', function (Request $request) {
        $request->validate(['file' => 'required|file|max:4096']);
        $file = $request->file('file');
        if (strtolower($file->getClientOriginalExtension()) !== 'md') {
            return back()->withErrors(['file' => 'Only .md files are allowed.']);
        }
        $content = file_get_contents($file->getRealPath());
        file_put_contents(public_path('manual.md'), $content);
        return redirect()->route('manual')->with('success', 'Manual imported successfully.');
    })->name('manual.import');
    Route::get('checker', CheckerDashboard::class)->name('checker.dashboard');
    Route::get('scanner', MobileScanner::class)->name('scanner');
    Route::get('supervisor', SupervisorDashboard::class)->name('supervisor.dashboard');
    Route::get('import-guide', fn () => view('import-guide'))->name('import.guide');
    Route::get('product-import-template', ProductImportTemplateController::class)->name('product-import.template');
    
    Route::get('purchase-requests/report/print', function (\Illuminate\Http\Request $request) {
        $branchId = $request->query('branch_id');
        $date = $request->query('date');
        $stateFilter = $request->query('state_filter', 'all');
        
        $paidStateIds = \App\Modules\Core\Workflow\Models\WorkflowState::where('name', 'Paid')->pluck('id')->toArray();
        $afterPaidStateIds = [];
        $queue = $paidStateIds;
        
        while (!empty($queue)) {
            $currentStateId = array_shift($queue);
            $nextStateIds = \App\Modules\Core\Workflow\Models\WorkflowTransition::where('from_state_id', $currentStateId)
                ->pluck('to_state_id')
                ->toArray();
                
            foreach ($nextStateIds as $nextStateId) {
                if (!in_array($nextStateId, $paidStateIds) && !in_array($nextStateId, $afterPaidStateIds)) {
                    $afterPaidStateIds[] = $nextStateId;
                    $queue[] = $nextStateId;
                }
            }
        }
        
        $query = \App\Modules\Purchase\Models\PurchaseItem::with(['purchaseRequest.branch', 'purchaseRequest.creator', 'productType'])
            ->whereHas('purchaseRequest', function ($q) use ($branchId, $date, $stateFilter, $paidStateIds, $afterPaidStateIds) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                if ($date) {
                    $q->whereDate('created_at', $date);
                }
                if ($stateFilter === 'paid_and_after') {
                    $q->whereIn('workflow_state_id', array_merge($paidStateIds, $afterPaidStateIds));
                } elseif ($stateFilter === 'before_paid') {
                    $q->whereNotIn('workflow_state_id', array_merge($paidStateIds, $afterPaidStateIds));
                }
            });

        $items = $query->get();
        
        $branchName = 'All Locations';
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) {
                $branchName = $branch->name;
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchase-report-print', [
            'items' => $items,
            'branchName' => $branchName,
            'date' => $date,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('purchased-items-report.pdf');
    })->name('purchase-requests.report.print');

    Route::get('purchase-requests/{record}/print', function (\App\Modules\Purchase\Models\PurchaseRequest $record) {
        $record->load(['branch', 'workflowState', 'items.productType', 'items.validationHistories.user', 'creator', 'statusUpdater']);
        
        \App\Modules\Purchase\Models\PurchaseRequestPrintLog::create([
            'purchase_request_id' => $record->id,
            'user_id' => auth()->id(),
            'printed_at' => now(),
        ]);

        return view('purchase-print', ['record' => $record]);
    })->name('purchase-requests.print');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
