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
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
