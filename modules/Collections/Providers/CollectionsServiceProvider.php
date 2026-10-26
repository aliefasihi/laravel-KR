<?php

/**
 * Khaneh Romantic — Collections module: pages, «از کالکشن …» on products, a home row
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Collections\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Modules\Modules;
use App\Core\Support\StorefrontHooks;
use Modules\Catalog\Models\Product;
use Modules\Collections\Models\Collection;

class CollectionsServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Product::resolveRelationUsing('collections', fn (Product $product) => $product->belongsToMany(Collection::class));

        StorefrontHooks::add('product.buybox', 'collections::partials.product-link', 5);
        // under the newest-products slider (HomeSliders); without that module, under the category circles
        StorefrontHooks::add(Modules::enabled('HomeSliders') ? 'home.after_newest' : 'home.after_categories', 'collections::partials.home', 20);
    }
}
