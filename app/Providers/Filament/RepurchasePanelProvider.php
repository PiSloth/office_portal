<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Repurchase\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class RepurchasePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('repurchase')
            ->path('repurchase')
            ->login()
            ->profile()
            ->favicon(asset('favicon.svg'))
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Profile & Settings')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): ?string => filament()->getProfileUrl()),
            ])
            ->colors([
                'primary' => Color::Teal,
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Back to Portal')
                    ->url(fn (): string => route('dashboard'))
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->sort(-1),
                \Filament\Navigation\NavigationItem::make('Stock App')
                    ->url('/stock')
                    ->icon('heroicon-o-archive-box')
                    ->sort(1),
                \Filament\Navigation\NavigationItem::make('System Admin')
                    ->url('/admin')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->sort(999),
            ])
            ->discoverResources(in: app_path('Filament/Repurchase/Resources'), for: 'App\Filament\Repurchase\Resources')
            ->discoverPages(in: app_path('Filament/Repurchase/Pages'), for: 'App\Filament\Repurchase\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Repurchase/Widgets'), for: 'App\Filament\Repurchase\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
