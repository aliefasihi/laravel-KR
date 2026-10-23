<?php

/**
 * Khaneh Romantic — Tracking module: Search Console & co. verification, analytics, pixels
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Tracking\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\StorefrontHooks;

class TrackingServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        StorefrontHooks::add('head.end', 'tracking::head', 5);
        StorefrontHooks::add('body.end', 'tracking::loader', 99);
    }
}
