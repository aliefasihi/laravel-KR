<?php

/**
 * Khaneh Romantic — Brand page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Support\ProductListing;
use Modules\Search\Support\ProductFinder;

class BrandController
{
    public function show(Request $request, string $slug, ProductListing $listing, ProductFinder $finder): View
    {
        $brand = Brand::query()->where('slug', $slug)->with('media')->firstOrFail();
        $filters = $listing->filters($request);
        $filters['brand'] = []; // the page is the brand

        [$products, $result] = $finder->paginate($listing->query($filters, $request, scopeBrandId: $brand->id), $brand->url());

        return view('catalog::brand.show', [
            'brand' => $brand,
            'products' => $products,
            'priceRange' => $result->priceRange,
            'filters' => $filters,
            'sorts' => ProductListing::SORTS,
        ]);
    }
}
