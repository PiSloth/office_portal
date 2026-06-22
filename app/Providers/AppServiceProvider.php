<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use App\Events\ProductChecked;
use App\Listeners\RunDecisionEngine;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register ProductChecked Event Listener
        Event::listen(ProductChecked::class, RunDecisionEngine::class);

        Notifications::alignment(Alignment::Center);
        Notifications::verticalAlignment(VerticalAlignment::Start);

        // Super-admin bypass for permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') || $user->hasRole('Super Admin') ? true : null;
        });

        Gate::define('manage-users', fn ($user) => $user->can('users.view') || $user->can('users.create') || $user->can('users.update') || $user->can('users.delete'));
        Gate::define('manage-roles', fn ($user) => $user->can('roles.view') || $user->can('roles.create') || $user->can('roles.update') || $user->can('roles.delete') || $user->can('roles.assign'));
        Gate::define('manage-permissions', fn ($user) => $user->can('permissions.view') || $user->can('permissions.create') || $user->can('permissions.update') || $user->can('permissions.delete') || $user->can('permissions.manage'));
    }
}
