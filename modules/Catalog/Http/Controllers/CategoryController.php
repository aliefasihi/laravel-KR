<?php

/**
 * Khaneh Romantic — Category page with GET filters (crawlable, no JS required)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Results and facet counts (brands, price range) come from the search engine.
 */

namespace Modules\Catalog\Http\Controllers;

use App\Core\Modules\Modules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Support\ProductListing;
use Modules\Search\Support\ProductFinder;

class CategoryController
{
    public function show(Request $request, string $path, ProductListing $listing, ProductFinder $finder): View
    {
        $category = Category::query()->byPath($path)->where('is_active', true)
            ->with(['parent', 'media', 'children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->with('media')])
            ->firstOrFail();

        $filters = $listing->filters($request);

        // category-specific filters from its attribute template (Attributes module)
        $attributes = Modules::enabled('Attributes') ? app(AttributeTemplate::class)->filtersFor($category->id) : collect();

        // withPath keeps the trailing slash in ?page= links (no extra 301)
        [$products, $result] = $finder->paginate(
            $listing->query($filters, $request, categoryIds: $category->descendantIds(), attributes: $attributes),
            $category->url(),
        );

        return view('catalog::category.show', [
            'category' => $category,
            'products' => $products,
            'brands' => $listing->facetBrands($result, $filters),
            'counts' => $result->brandCounts,
            'priceRange' => $result->priceRange,
            'attributes' => $attributes,
            'valueCounts' => $result->valueCounts,
            'filters' => $filters,
            'filtered' => $listing->isFiltered($filters),
            'sorts' => ProductListing::SORTS,
        ]);
    }
}
