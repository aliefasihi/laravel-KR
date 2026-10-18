<?php

/**
 * Khaneh Romantic — Engine answer: ordered ids, total and facet counts
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Support;

final class SearchResult
{
    /**
     * @param  list<int>  $ids  product ids in display order (current page only)
     * @param  array<int, int>  $brandCounts  brand_id => products (ignoring the brand filter itself)
     * @param  array{min: int, max: int}|null  $priceRange  Rials (ignoring the price filter itself)
     */
    public function __construct(
        public readonly array $ids,
        public readonly int $total,
        public readonly array $brandCounts = [],
        public readonly ?array $priceRange = null,
        public readonly string $engine = 'database',
    ) {}
}
