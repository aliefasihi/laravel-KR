<?php

/**
 * Khaneh Romantic — Core provider: registers the installed modules that are switched on
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Installed = config/modules.php; on/off = admin «ماژول‌ها» page (App\Core\Modules\ModuleRegistry).
 */

namespace App\Core\Modules;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);

        foreach ($this->app->make(ModuleRegistry::class)->providers() as $provider) {
            $this->app->register($provider);
        }
    }
}
