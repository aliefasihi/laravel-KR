<?php

/**
 * Khaneh Romantic — Search results page (/search?q=)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Typo-tolerant, synonym-aware results from the search engine, with the same filters as
 * category pages. Every search is counted for the admin «گزارش جستجو» report.
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Catalog\Support\ProductListing;
use Modules\Search\Support\ProductFinder;

class SearchController
{
    public function __invoke(Request $request, ProductListing $listing, ProductFinder $finder): View
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $filters = $listing->filters($request, ProductListing::SEARCH_SORTS);

        if ($q === '') {
            return view('catalog::search.index', ['q' => '', 'products' => null]);
        }

        [$products, $result] = $finder->paginate($listing->query($filters, $request, term: $q), route('catalog.search'));

        if ($products->currentPage() === 1 && ! $listing->isFiltered($filters, 'relevance')) {
            $finder->log($q, $result->total);
        }

        return view('catalog::search.index', [
            'q' => $q,
            'products' => $products,
            'brands' => $listing->facetBrands($result, $filters),
            'counts' => $result->brandCounts,
            'priceRange' => $result->priceRange,
            'filters' => $filters,
            'filtered' => $listing->isFiltered($filters, 'relevance'),
            'sorts' => ProductListing::SEARCH_SORTS,
        ]);
    }
}
