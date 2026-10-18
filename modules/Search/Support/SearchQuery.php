<?php

/**
 * Khaneh Romantic — A product search/listing request, engine-agnostic
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Support;

final class SearchQuery
{
    public const SORTS = ['relevance', 'newest', 'price_asc', 'price_desc', 'discount'];

    /**
     * @param  list<int>  $brandIds  shopper's brand filter (any of)
     * @param  list<int>|null  $categoryIds  page scope: any of these categories (already includes descendants)
     * @param  int|null  $minPrice  Rials
     * @param  int|null  $maxPrice  Rials
     */
    public function __construct(
        public string $term = '',
        public array $brandIds = [],
        public ?array $categoryIds = null,
        public ?int $scopeBrandId = null,
        public bool $inStock = false,
        public bool $onSale = false,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public string $sort = 'newest',
        public int $page = 1,
        public int $perPage = 24,
        public bool $withFacets = true,
    ) {
        $this->term = SearchText::normalize($term);
        $this->brandIds = array_values(array_unique(array_map('intval', $brandIds)));
        $this->page = max(1, $page);
        $this->perPage = max(1, min(100, $perPage));

        if (! in_array($this->sort, self::SORTS, true) || ($this->sort === 'relevance' && $this->term === '')) {
            $this->sort = 'newest';
        }
    }

    public function hasTerm(): bool
    {
        return $this->term !== '';
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /** Same query without the shopper's brand filter (for brand facet counts). */
    public function withoutBrandFilter(): self
    {
        $copy = clone $this;
        $copy->brandIds = [];

        return $copy;
    }

    /** Same query without the price range (for the price facet's full range). */
    public function withoutPriceFilter(): self
    {
        $copy = clone $this;
        $copy->minPrice = $copy->maxPrice = null;

        return $copy;
    }
}
