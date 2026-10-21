<?php

/**
 * Khaneh Romantic — «Recently viewed» module
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The visitor's own browser remembers the last products they opened (localStorage «kr-recent»);
 * product pages and the home page show them in a row. Works with the cached home page because
 * the row is filled in the browser.
 */

namespace Modules\RecentlyViewed\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\StorefrontHooks;

class RecentlyViewedServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'recent';
    }

    public function boot(): void
    {
        parent::boot();

        // product page: record this product, show the others (under related / reviews)
        StorefrontHooks::add('product.sections', fn (array $data) => view('recent::partials.slot', ['current' => $data['product']->id]), 90);
        // home page (part of the cached HTML — the row itself comes from the browser)
        StorefrontHooks::add('home.before_journal', fn () => view('recent::partials.slot', ['current' => 0]), 50);
    }
}
