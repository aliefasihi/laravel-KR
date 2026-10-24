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
use App\Core\Settings\SiteSettings;
use App\Filament\Auth\Login;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Pages\SettingsPage;
use App\Filament\Support\AdminSearch;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
            ->login(Login::class)
            ->passwordReset()
            ->profile()
            // name, logo and favicon from تنظیمات ← عمومی
            ->brandName(fn () => config('khaneh.store.name'))
            ->brandLogo(fn () => SiteSettings::logoUrl())
            ->darkModeBrandLogo(fn () => SiteSettings::logoUrl(light: true) ?? SiteSettings::logoUrl())
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => SiteSettings::faviconUrl())
            ->colors([
                // brand rose; 500–700 a little deeper than Filament's generated shades so primary
                // buttons get white text (AA contrast) instead of a pale pink fill
                'primary' => array_replace(Color::hex('#cc3366'), [
                    500 => 'oklch(0.6 0.19 6)',
                    600 => 'oklch(0.545 0.185 6)',
                    700 => 'oklch(0.48 0.165 6)',
                ]),
                'danger' => Color::Red,
                'gray' => Color::Slate,
            ])
            ->font('Vazirmatn', url: asset('fonts/fonts.css'), provider: LocalFontProvider::class)
            ->defaultAvatarProvider(InitialsAvatarProvider::class) // no ui-avatars.com request on every page
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            // the bell: low stock and other alerts for the staff (checked every minute)
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            // SPA navigation: page changes swap only the content (no full reload of Filament's CSS/JS).
            // On the live server links are also prefetched on hover so most clicks open instantly; not
            // on a local dev server (php artisan serve has few workers: every hover would queue a
            // whole page and make the real clicks wait).
            ->spa(hasPrefetching: app()->isProduction())
            // one search box for menu pages, every settings field and records (Ctrl/⌘ + K)
            ->globalSearch(AdminSearch::class)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->globalSearchDebounce('250ms')
            // a settings result opens its tab and scrolls to the field (?tab=…&field=…)
            ->renderHook(PanelsRenderHook::PAGE_END, fn () => view('filament.settings-jump'), scopes: SettingsPage::class)
            ->navigationGroups([
                'فروش',
                'ارتباط با مشتری',
                'باشگاه مشتریان',
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
