<?php

/**
 * Khaneh Romantic — Core provider: registers the installed modules that are switched on
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Installed = config/modules.php; on/off = admin «ماژول‌ها» page (App\Core\Modules\ModuleRegistry).
 * Also: store settings (App\Core\Settings\Settings) and the @storefrontHook Blade directive.
 */

namespace App\Core\Modules;

use App\Core\Settings\Settings;
use App\Core\Support\StorefrontHooks;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(Settings::class);
        StorefrontHooks::flush(); // static registry: start clean for each app (tests, Octane workers)

        foreach ($this->app->make(ModuleRegistry::class)->providers() as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void
    {
        // @storefrontHook('header.actions') — modules add their pieces to the storefront layout
        Blade::directive('storefrontHook', fn (string $expression) => '<?php echo '.StorefrontHooks::class.'::render('.$expression.'); ?>');
    }
}
