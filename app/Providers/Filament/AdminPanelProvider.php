<?php

/**
 * Khaneh Romantic — Admin panel (/admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Resources live inside their modules (modules/{Name}/Filament/Resources) and are
 * discovered automatically. App-level resources (users) live in app/Filament.
 */

namespace App\Providers\Filament;

use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile()
            ->brandName('خانه رمانتیک')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex('#0e7c7b'),
                'danger' => Color::hex('#b3263e'),
                'gray' => Color::Slate,
            ])
            ->font('Vazirmatn', url: asset('fonts/fonts.css'), provider: LocalFontProvider::class)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'کاتالوگ',
                'محتوا',
                'بازاریابی',
                'سئو',
                'تنظیمات',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        // Every module may ship its own resources: modules/{Name}/Filament/Resources
        foreach (glob(base_path('modules/*/Filament/Resources'), GLOB_ONLYDIR) ?: [] as $dir) {
            $module = basename(dirname($dir, 2));
            $panel->discoverResources(in: $dir, for: "Modules\\{$module}\\Filament\\Resources");
        }

        return $panel;
    }
}
