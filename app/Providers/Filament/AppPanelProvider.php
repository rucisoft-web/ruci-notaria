<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dasbor;
use App\Http\Controllers\TamuController;
use App\Http\Middleware\BatasiTamu;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->passwordReset()
            ->profile()
            ->brandName(fn () => 'Ruci Notaria')
            ->brandLogo(fn () => asset('img/logo/ruci-notaria.svg'))
            ->darkModeBrandLogo(fn () => asset('img/logo/ruci-notaria-putih.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                // Hijau tua kebiruan dengan aksen emas: kesan resmi dan tenang untuk kantor notaris.
                'primary' => [
                    50 => '#eef6f5', 100 => '#d3e9e6', 200 => '#a7d2cc', 300 => '#74b5ad', 400 => '#45958c',
                    500 => '#2a7a71', 600 => '#1d635b', 700 => '#174f49', 800 => '#123f3a', 900 => '#0e322e', 950 => '#07201d',
                ],
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: fn () => (bool) config('notaria.mfa_required') && ! auth()->user()?->isTamu(),
            )
            ->navigationGroups([
                NavigationGroup::make('Kerja Harian'),
                NavigationGroup::make('Buku Daftar & Protokol'),
                NavigationGroup::make('Keuangan'),
                NavigationGroup::make('Panduan'),
                NavigationGroup::make('Pengaturan')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dasbor::class])
            ->widgets([])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->globalSearch(false)
            ->renderHook(PanelsRenderHook::BODY_START, fn () => config('notaria.demo.enabled') ? view('filament.label-data-contoh') : '')
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('filament.masuk-tamu', ['tamu' => TamuController::tersedia()]))
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => new HtmlString('<link rel="stylesheet" href="'.asset('css/notaria.css').'?v='.@filemtime(public_path('css/notaria.css')).'">'))
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
            ->authMiddleware([Authenticate::class, BatasiTamu::class]);
    }
}
