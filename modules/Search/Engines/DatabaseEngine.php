<?php

/**
 * Khaneh Romantic — Search without a search server (SQL over normalized text)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Words are matched against product_search_texts (normalized name, brand, SKU, categories),
 * with the configured synonyms. Every word must match; when that finds nothing, products
 * matching most of the words are shown instead. No typo tolerance — that is what
 * Meilisearch/Elasticsearch add. Filters and prices are read live from the catalog tables.
 * Terms are normalized to letters/digits/spaces (SearchText), so they never carry LIKE wildcards.
 */

namespace Modules\Search\Engines;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Search\Contracts\SearchEngine;
use Modules\Search\Support\ProductDocuments;
use Modules\Search\Support\SearchQuery;
use Modules\Search\Support\SearchResult;
use Modules\Search\Support\SearchText;

class DatabaseEngine implements SearchEngine
{
    private const TABLE = 'product_search_texts';

    private const CURRENT_PRICE = 'CASE WHEN sale_price IS NOT NULL AND (sale_starts_at IS NULL OR sale_starts_at <= ?) AND (sale_ends_at IS NULL OR sale_ends_at > ?) THEN sale_price ELSE price END';

    public function name(): string
    {
        return 'database';
    }

    public function search(SearchQuery $query): SearchResult
    {
        $units = $this->units($query->term);
        $matchAll = true;

        $total = $this->filtered($query, $units, true)->count();

        // Nothing has every word? Show what matches most of them rather than an empty page.
        if ($total === 0 && count($units) > 1) {
            $matchAll = false;
            $total = $this->filtered($query, $units, false)->count();
        }

        $ids = $total === 0 ? [] : $this->sorted($this->filtered($query, $units, $matchAll), $query, $units)
            ->offset($query->offset())
            ->limit($query->perPage)
            ->pluck('products.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! $query->withFacets) {
            return new SearchResult($ids, $total, engine: $this->name());
        }

        $brandCounts = $this->filtered($query->withoutBrandFilter(), $units, $matchAll)
            ->toBase()
            ->select('products.brand_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('products.brand_id')
            ->pluck('aggregate', 'brand_id')
            ->map(fn ($n) => (int) $n)
            ->all();

        $now = Carbon::now();
        $range = DB::query()
            ->fromSub(
                $this->filtered($query->withoutPriceFilter(), $units, $matchAll)->toBase()
                    ->select('products.id')
                    ->selectSub(ProductVariant::query()->toBase()
                        ->selectRaw('MIN('.self::CURRENT_PRICE.')', [$now, $now])
                        ->whereColumn('product_id', 'products.id')
                        ->where('is_active', true), 'p'),
                'priced',
            )
            ->selectRaw('MIN(p) as min_price, MAX(p) as max_price')
            ->first();

        return new SearchResult(
            $ids,
            $total,
            $brandCounts,
            $range && $range->min_price !== null ? ['min' => (int) $range->min_price, 'max' => (int) $range->max_price] : null,
            $this->name(),
            $this->valueCounts($query, $units, $matchAll),
        );
    }

    /**
     * Products per attribute value for the attributes the page shows. Each attribute is counted
     * without its own filter (so ticking «استیل» doesn't hide «چینی»'s count): one query for all
     * attributes without an active filter, plus one per attribute that has one.
     *
     * @return array<int, int>
     */
    private function valueCounts(SearchQuery $query, array $units, bool $matchAll): array
    {
        if ($query->facetAttributes === []) {
            return [];
        }

        $count = fn (SearchQuery $scope, array $attributeIds) => DB::table('product_attribute_values')
            ->whereIn('attribute_id', $attributeIds)
            ->whereIn('product_id', $this->filtered($scope, $units, $matchAll)->toBase()->select('products.id'))
            ->selectRaw('attribute_value_id, COUNT(DISTINCT product_id) as aggregate')
            ->groupBy('attribute_value_id')
            ->pluck('aggregate', 'attribute_value_id')
            ->map(fn ($n) => (int) $n)
            ->all();

        $wanted = array_keys($query->facetAttributes);
        $filtered = array_keys($query->attributeFilters);
        $plain = array_values(array_diff($wanted, $filtered));

        $counts = $plain ? $count($query, $plain) : [];
        foreach (array_intersect($wanted, $filtered) as $attributeId) {
            $counts += $count($query->withoutAttributeFilter($attributeId), [$attributeId]);
        }

        return $counts;
    }

    public function suggest(string $term, int $limit = 6): array
    {
        return $this->search(new SearchQuery(term: $term, sort: 'relevance', perPage: $limit, withFacets: false))->ids;
    }

    public function upsert(array $documents): void
    {
        if ($documents === []) {
            return;
        }

        $texts = app(ProductDocuments::class);
        $now = Carbon::now();

        foreach (array_chunk($documents, 500) as $chunk) {
            DB::table(self::TABLE)->upsert(
                array_map(fn (array $doc) => ['product_id' => $doc['id'], 'text' => ' '.$texts->text($doc).' ', 'updated_at' => $now], $chunk),
                ['product_id'],
                ['text', 'updated_at'],
            );
        }
    }

    public function delete(array $ids): void
    {
        if ($ids !== []) {
            DB::table(self::TABLE)->whereIn('product_id', $ids)->delete();
        }
    }

    public function reset(): void
    {
        DB::table(self::TABLE)->delete();
    }

    public function count(): ?int
    {
        return DB::table(self::TABLE)->count();
    }

    /**
     * The query split into match units: a configured synonym phrase found in the query is one unit
     * (any of its equivalents may match), every other word is a unit of its own (+ its synonyms).
     *
     * @return list<list<string>>
     */
    private function units(string $term): array
    {
        if ($term === '') {
            return [];
        }

        $units = [];
        $rest = ' '.$term.' ';

        // Longest phrases first so "سرویس غذاخوری" wins over a one-word synonym inside it.
        $phrases = [];
        foreach (SearchText::synonymGroups() as $group) {
            foreach ($group as $phrase) {
                $phrases[] = [$phrase, $group];
            }
        }
        usort($phrases, fn ($a, $b) => mb_strlen($b[0]) <=> mb_strlen($a[0]));

        foreach ($phrases as [$phrase, $group]) {
            if (str_contains($rest, ' '.$phrase.' ')) {
                $units[] = $group;
                $rest = str_replace(' '.$phrase.' ', ' ', $rest);
            }
        }

        foreach (explode(' ', trim($rest)) as $word) {
            if ($word !== '' && (mb_strlen($word) > 1 || ctype_digit($word))) {
                $units[] = [$word];
            }
        }

        return array_values(array_map('array_values', array_map('array_unique', $units)));
    }

    /** @param  list<list<string>>  $units */
    private function filtered(SearchQuery $query, array $units, bool $matchAll): Builder
    {
        $now = Carbon::now();
        $variant = fn ($v) => $v->where('is_active', true);

        return Product::query()
            ->active()
            ->when($query->scopeBrandId, fn ($q, $id) => $q->where('products.brand_id', $id))
            ->when($query->categoryIds !== null, fn ($q) => $q->whereExists(fn ($e) => $e
                ->selectRaw('1')->from('category_product')
                ->whereColumn('category_product.product_id', 'products.id')
                ->whereIn('category_product.category_id', $query->categoryIds ?: [0])))
            ->when($query->brandIds, fn ($q, $ids) => $q->whereIn('products.brand_id', $ids))
            ->when($query->inStock, fn ($q) => $q->whereHas('variants', fn ($v) => $variant($v)->where('stock', '>', 0)))
            ->when($query->onSale, fn ($q) => $q->whereHas('variants', fn ($v) => $variant($v)
                ->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')
                ->where(fn ($w) => $w->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now))
                ->where(fn ($w) => $w->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', $now))))
            ->when($query->minPrice, fn ($q, $min) => $q->whereHas('variants', fn ($v) => $variant($v)->whereRaw('('.self::CURRENT_PRICE.') >= ?', [$now, $now, $min])))
            ->when($query->maxPrice, fn ($q, $max) => $q->whereHas('variants', fn ($v) => $variant($v)->whereRaw('('.self::CURRENT_PRICE.') <= ?', [$now, $now, $max])))
            // attribute filters: any of the chosen values, for every attribute chosen
            ->when($query->attributeFilters, function ($q, $filters) {
                foreach ($filters as $attributeId => $valueIds) {
                    $q->whereExists(fn ($e) => $e->selectRaw('1')->from('product_attribute_values as pav')
                        ->whereColumn('pav.product_id', 'products.id')
                        ->where('pav.attribute_id', $attributeId)
                        ->whereIn('pav.attribute_value_id', $valueIds));
                }
            })
            ->when($units !== [], fn ($q) => $q->whereIn('products.id', function ($sub) use ($units, $matchAll) {
                $sub->select('product_id')->from(self::TABLE)->where(function ($w) use ($units, $matchAll) {
                    foreach ($units as $alternatives) {
                        $clause = fn ($u) => $u->where(function ($any) use ($alternatives) {
                            foreach ($alternatives as $alt) {
                                $any->orWhere('text', 'like', '%'.$alt.'%');
                            }
                        });
                        $matchAll ? $w->where($clause) : $w->orWhere($clause);
                    }
                });
            }));
    }

    /** @param  list<list<string>>  $units */
    private function sorted(Builder $builder, SearchQuery $query, array $units): Builder
    {
        $now = Carbon::now();
        $minPrice = ProductVariant::query()->toBase()
            ->selectRaw('MIN('.self::CURRENT_PRICE.')', [$now, $now])
            ->whereColumn('product_id', 'products.id')->where('is_active', true);
        $inStock = 'CASE WHEN EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = products.id AND v.is_active = 1 AND v.stock > 0) THEN 0 ELSE 1 END';

        // Sold-out products always go last (whatever the sort), like the big Iranian stores do.
        if ($query->sort !== 'relevance') {
            $builder->orderByRaw($inStock);
        }

        return match ($query->sort) {
            'price_asc' => $builder->orderBy($minPrice),
            'price_desc' => $builder->orderByDesc($minPrice),
            'discount' => $builder->orderByDesc(ProductVariant::query()->toBase()
                ->selectRaw('MAX(price - ('.self::CURRENT_PRICE.'))', [$now, $now])
                ->whereColumn('product_id', 'products.id')->where('is_active', true)),
            'relevance' => $this->byRelevance($builder, $query, $units)->orderByRaw($inStock)->orderByDesc('products.id'),
            default => $builder->orderByDesc('products.id'),
        };
    }

    /** Whole phrase first, then names that start with the query, then most matched words. */
    private function byRelevance(Builder $builder, SearchQuery $query, array $units): Builder
    {
        $builder->leftJoin(self::TABLE.' as st', 'st.product_id', '=', 'products.id')->select('products.*');

        $phrase = '%'.$query->term.'%';
        $starts = ' '.$query->term.'%';
        $builder->orderByRaw('CASE WHEN st.text LIKE ? THEN 0 WHEN st.text LIKE ? THEN 1 ELSE 2 END', [$starts, $phrase]);

        if (count($units) > 1) {
            $parts = [];
            $bindings = [];
            foreach ($units as $alternatives) {
                $parts[] = '(CASE WHEN '.implode(' OR ', array_fill(0, count($alternatives), 'st.text LIKE ?')).' THEN 1 ELSE 0 END)';
                foreach ($alternatives as $alt) {
                    $bindings[] = '%'.$alt.'%';
                }
            }
            $builder->orderByRaw('('.implode(' + ', $parts).') DESC', $bindings);
        }

        return $builder;
    }
}
