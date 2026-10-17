<?php

/**
 * Khaneh Romantic — Catalog module (brands, categories, products, variants, search)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Modules\Catalog\Livewire\SearchBox;
use Modules\Catalog\View\Composers\NavigationComposer;

class CatalogServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Livewire::component('catalog.search-box', SearchBox::class);

        View::composer(['components.site.header', 'components.site.footer'], NavigationComposer::class);
    }
}
