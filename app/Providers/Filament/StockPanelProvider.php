<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Stock\Pages\Dashboard;
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

class StockPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('stock')
            ->path('stock')
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
                'primary' => Color::Amber,
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Back to Portal')
                    ->url(fn (): string => route('dashboard'))
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->sort(-1),
                \Filament\Navigation\NavigationItem::make('System Admin')
                    ->url('/admin')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->sort(999),
            ])
            ->discoverResources(in: app_path('Filament/Stock/Resources'), for: 'App\Filament\Stock\Resources')
            ->discoverPages(in: app_path('Filament/Stock/Pages'), for: 'App\Filament\Stock\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Stock/Widgets'), for: 'App\Filament\Stock\Widgets')
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
