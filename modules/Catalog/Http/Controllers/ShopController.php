<?php

/**
 * Khaneh Romantic — All products (/shop, kept from WordPress)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Support\ProductListing;

class ShopController
{
    public function __invoke(Request $request, ProductListing $listing): View
    {
        $filters = $listing->filters($request);
        $base = $listing->base();

        return view('catalog::shop.index', [
            'products' => $listing->apply(clone $base, $filters)->paginate(24)->withQueryString(),
            'brands' => $listing->brandsFor($base),
            'categories' => Category::query()->whereNull('parent_id')->where('is_active', true)->with('media')->orderBy('sort')->get(),
            'filters' => $filters,
            'filtered' => $listing->isFiltered($filters),
            'sorts' => ProductListing::SORTS,
        ]);
    }
}
