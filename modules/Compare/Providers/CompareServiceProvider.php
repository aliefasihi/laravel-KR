<?php

/**
 * Khaneh Romantic — Compare module: «مقایسه» button, the compare bar and page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Compare\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\StorefrontHooks;

class CompareServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        StorefrontHooks::add('product.buybox', 'compare::partials.button', 25);
        StorefrontHooks::add('body.end', 'compare::partials.bar', 40);
    }
}
