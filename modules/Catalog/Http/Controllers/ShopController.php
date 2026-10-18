<?php

/**
 * Khaneh Romantic — All products (/shop, kept from WordPress)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use App\Core\Support\StoreCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Support\ProductListing;
use Modules\Search\Support\ProductFinder;

class ShopController
{
    public function __invoke(Request $request, ProductListing $listing, ProductFinder $finder): View
    {
        $filters = $listing->filters($request);
        [$products, $result] = $finder->paginate($listing->query($filters, $request), route('catalog.shop'));

        return view('catalog::shop.index', [
            'products' => $products,
            'brands' => $listing->facetBrands($result, $filters),
            'counts' => $result->brandCounts,
            'priceRange' => $result->priceRange,
            'categories' => StoreCache::remember('shop.categories', fn () => Category::query()
                ->whereNull('parent_id')->where('is_active', true)->with('media')->orderBy('sort')->get()),
            'filters' => $filters,
            'filtered' => $listing->isFiltered($filters),
            'sorts' => ProductListing::SORTS,
        ]);
    }
}
