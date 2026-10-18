<?php

/**
 * Khaneh Romantic — Catalog module (brands, categories, products, variants; search lives in Modules\Search)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\Catalog\View\Composers\NavigationComposer;

class CatalogServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        View::composer(['components.site.header', 'components.site.footer'], NavigationComposer::class);
    }
}
