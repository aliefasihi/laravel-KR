<?php

/**
 * Khaneh Romantic — Admin panel (/admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Resources live inside their modules (modules/{Name}/Filament/Resources) and are
 * discovered automatically. App-level resources (users) live in app/Filament.
 */

namespace App\Providers\Filament;

use App\Core\Modules\ModuleRegistry;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
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
                'primary' => Color::hex('#cc3366'), // brand rose, same as the storefront
                'danger' => Color::Red,
                'gray' => Color::Slate,
            ])
            ->font('Vazirmatn', url: asset('fonts/fonts.css'), provider: LocalFontProvider::class)
            ->defaultAvatarProvider(InitialsAvatarProvider::class) // no ui-avatars.com request on every page
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            // SPA navigation: page changes swap only the content (no full reload of Filament's CSS/JS),
            // and links are prefetched on hover so most clicks open instantly.
            ->spa(hasPrefetching: true)
            ->navigationGroups([
                'کاتالوگ',
                'محتوا',
                'بازاریابی',
                'سئو',
                'تنظیمات',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
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

        // Every switched-on module may ship resources, pages and widgets: modules/{Name}/Filament/…
        $registry = app(ModuleRegistry::class);
        foreach ($registry->all() as $module => $meta) {
            if (! $registry->enabled($module)) {
                continue;
            }
            foreach (['Resources' => 'discoverResources', 'Pages' => 'discoverPages', 'Widgets' => 'discoverWidgets'] as $kind => $discover) {
                if (is_dir($dir = $meta['path'].'/Filament/'.$kind)) {
                    $panel->{$discover}(in: $dir, for: "Modules\\{$module}\\Filament\\{$kind}");
                }
            }
        }

        return $panel;
    }
}
