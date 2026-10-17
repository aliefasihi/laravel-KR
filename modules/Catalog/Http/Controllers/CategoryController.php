<?php

/**
 * Khaneh Romantic — Category page with GET filters (crawlable, no JS required)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Support\ProductListing;

class CategoryController
{
    public function show(Request $request, string $path, ProductListing $listing): View
    {
        $category = Category::query()->byPath($path)->where('is_active', true)
            ->with(['parent', 'media', 'children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->with('media')])
            ->firstOrFail();

        $filters = $listing->filters($request);
        $base = $listing->base($category->descendantIds());

        $products = $listing->apply(clone $base, $filters)
            ->paginate(24)
            ->withPath($category->url())   // keeps the trailing slash in ?page= links (no extra 301)
            ->withQueryString();

        return view('catalog::category.show', [
            'category' => $category,
            'products' => $products,
            'brands' => $listing->brandsFor($base),
            'filters' => $filters,
            'filtered' => $listing->isFiltered($filters),
            'sorts' => ProductListing::SORTS,
        ]);
    }
}
