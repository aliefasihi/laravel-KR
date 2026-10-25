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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Support\ProductListing;
use Modules\Search\Support\ProductFinder;

class CategoryController
{
    public function show(Request $request, string $path, ProductListing $listing, ProductFinder $finder): View|RedirectResponse
    {
        // the canonical address has no trailing slash (one 301; a fallback route skips CanonicalTrailingSlash)
        if (str_ends_with($request->getPathInfo(), '/')) {
            $query = $request->getQueryString();

            return redirect()->to(rtrim($request->url(), '/').($query ? '?'.$query : ''), 301);
        }

        // unknown path: 404 → the redirects table and the old-URL matcher get their turn (SeoServiceProvider)
        $category = Category::query()->byPath($path)->where('is_active', true)
            ->with(['parent', 'media', 'children' => fn ($q) => $q->visible()->orderBy('sort')->with('media')])
            ->firstOrFail();

        $filters = $listing->filters($request);

        // category-specific filters from its attribute template (Attributes module)
        $attributes = Modules::enabled('Attributes') ? app(AttributeTemplate::class)->filtersFor($category->id) : collect();

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
