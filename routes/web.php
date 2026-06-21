<?php

use App\Livewire\CheckerDashboard;
use App\Livewire\MobileScanner;
use App\Livewire\SupervisorDashboard;
use App\Http\Controllers\ProductImportTemplateController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
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
