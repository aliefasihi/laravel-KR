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

class BrandController
{
    public function show(Request $request, string $slug, ProductListing $listing): View
    {
        $brand = Brand::query()->where('slug', $slug)->with('media')->firstOrFail();
        $filters = $listing->filters($request);

        return view('catalog::brand.show', [
            'brand' => $brand,
            'products' => $listing->apply($listing->base(brandId: $brand->id), $filters)->paginate(24)->withQueryString(),
            'filters' => $filters,
            'sorts' => ProductListing::SORTS,
        ]);
    }
}
