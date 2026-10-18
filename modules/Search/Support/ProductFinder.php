<?php

/**
 * Khaneh Romantic — Product pages ask this for results: engine ids → products, paginated
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Catalog\Models\Product;
use Modules\Search\Models\SearchTerm;

class ProductFinder
{
    public function __construct(private SearchManager $search) {}

    /**
     * @return array{0: LengthAwarePaginator, 1: SearchResult}
     */
    public function paginate(SearchQuery $query, string $path): array
    {
        $result = $this->search->search($query);

        $paginator = (new LengthAwarePaginator(
            $this->load($result->ids),
            $result->total,
            $query->perPage,
            $query->page,
            ['path' => $path, 'pageName' => 'page'],
        ))->withQueryString();

        return [$paginator, $result];
    }

    /** Instant suggestions: products in ranking order. */
    public function suggest(string $term, int $limit = 6): Collection
    {
        return SearchText::normalize($term) === '' ? collect() : $this->load($this->search->suggest($term, $limit));
    }

    /** Remember the search for the admin report (one small UPDATE; never breaks the page). */
    public function log(string $term, int $results): void
    {
        $term = SearchText::normalize($term);

        if ($term === '' || mb_strlen($term) > 100 || ! config('modules.search.log_terms', true)) {
            return;
        }

        rescue(fn () => SearchTerm::record($term, $results), report: false);
    }

    /** @param  list<int>  $ids */
    private function load(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $position = array_flip($ids);

        return Product::query()
            ->active()
            ->with(['brand', 'variants', 'media'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $p) => $position[$p->id] ?? PHP_INT_MAX)
            ->values();
    }
}
