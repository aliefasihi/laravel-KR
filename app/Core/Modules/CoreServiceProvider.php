<?php

/**
 * Khaneh Romantic — Core provider: registers the modules enabled in config/modules.php
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Core\Modules;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (config('modules.enabled', []) as $provider) {
            $this->app->register($provider);
        }
    }
}
