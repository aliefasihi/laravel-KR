<?php

/**
 * Khaneh Romantic — Shared product listing (shop, category, brand, search): filters + sort
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Filters come from plain GET params so every state has a crawlable URL:
 *   ?brand[]=goldkish&in_stock=1&sale=1&min=500000&max=3000000&sort=price_asc
 * Prices in the URL are Toman (what shoppers see); the database is Rials.
 * Results come from the search engine (Modules\Search) via query(); apply() is the plain-SQL
 * variant still used for the home page's cached blocks.
 */

namespace Modules\Catalog\Support;

use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Search\Support\SearchQuery;
use Modules\Search\Support\SearchResult;

class ProductListing
{
    public const SORTS = [
        'newest' => 'جدیدترین',
        'price_asc' => 'ارزان‌ترین',
        'price_desc' => 'گران‌ترین',
        'discount' => 'بیشترین تخفیف',
    ];

    /** Sorts on the search results page (relevance first). */
    public const SEARCH_SORTS = ['relevance' => 'مرتبط‌ترین'] + self::SORTS;

    /** @return array{brand: list<string>, in_stock: bool, sale: bool, min: ?int, max: ?int, sort: string} */
    public function filters(Request $request, array $sorts = self::SORTS): array
    {
        $toman = fn (string $key) => ($v = (int) PersianText::toLatinDigits((string) $request->query($key))) > 0 ? $v : null;

        return [
            'brand' => array_values(array_filter((array) $request->query('brand', []), 'is_string')),
            'in_stock' => $request->boolean('in_stock'),
            'sale' => $request->boolean('sale'),
            'min' => $toman('min'),
            'max' => $toman('max'),
            'sort' => array_key_exists((string) $request->query('sort'), $sorts) ? (string) $request->query('sort') : array_key_first($sorts),
        ];
    }

    /** URL filters → engine query (brand slugs resolved to ids, Toman → Rials). */
    public function query(array $filters, Request $request, string $term = '', ?array $categoryIds = null, ?int $scopeBrandId = null, int $perPage = 24): SearchQuery
    {
        return new SearchQuery(
            term: $term,
            brandIds: $filters['brand'] ? Brand::query()->whereIn('slug', $filters['brand'])->pluck('id')->all() : [],
            categoryIds: $categoryIds,
            scopeBrandId: $scopeBrandId,
            inStock: $filters['in_stock'],
            onSale: $filters['sale'],
            minPrice: $filters['min'] !== null ? $filters['min'] * 10 : null,
            maxPrice: $filters['max'] !== null ? $filters['max'] * 10 : null,
            sort: $filters['sort'],
            page: max(1, (int) $request->query('page', 1)),
            perPage: $perPage,
        );
    }

    /** Brands for the filter sidebar: those with results (facets) plus any already selected. */
    public function facetBrands(SearchResult $result, array $filters): Collection
    {
        return Brand::query()
            ->whereIn('id', array_keys($result->brandCounts))
            ->when($filters['brand'], fn ($q, $slugs) => $q->orWhereIn('slug', $slugs))
            ->orderBy('name_fa')
            ->get();
    }

    public function isFiltered(array $filters, string $defaultSort = 'newest'): bool
    {
        return $filters['brand'] !== [] || $filters['in_stock'] || $filters['sale']
            || $filters['min'] !== null || $filters['max'] !== null || $filters['sort'] !== $defaultSort;
    }

    /** Base scope: active products, optionally inside a set of categories or one brand. */
    public function base(?array $categoryIds = null, ?int $brandId = null): Builder
    {
        return Product::query()
            ->active()
            ->when($categoryIds !== null, fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds)))
            ->when($brandId !== null, fn ($q) => $q->where('brand_id', $brandId));
    }

    public function apply(Builder $query, array $filters): Builder
    {
        $variant = fn ($v) => $v->where('is_active', true);
        $current = 'CASE WHEN sale_price IS NOT NULL AND (sale_starts_at IS NULL OR sale_starts_at <= ?) AND (sale_ends_at IS NULL OR sale_ends_at > ?) THEN sale_price ELSE price END';
        $now = now();

        return $query
            ->when($filters['brand'], fn ($q, $slugs) => $q->whereHas('brand', fn ($b) => $b->whereIn('slug', $slugs)))
            ->when($filters['in_stock'], fn ($q) => $q->whereHas('variants', fn ($v) => $variant($v)->where('stock', '>', 0)))
            ->when($filters['sale'], fn ($q) => $q->whereHas('variants', fn ($v) => $variant($v)
                ->whereNotNull('sale_price')
                ->where(fn ($w) => $w->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now))
                ->where(fn ($w) => $w->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', $now))))
            ->when($filters['min'], fn ($q, $min) => $q->whereHas('variants', fn ($v) => $variant($v)->whereRaw("($current) >= ?", [$now, $now, $min * 10])))
            ->when($filters['max'], fn ($q, $max) => $q->whereHas('variants', fn ($v) => $variant($v)->whereRaw("($current) <= ?", [$now, $now, $max * 10])))
            ->addSelect(['min_price' => ProductVariant::query()
                ->selectRaw("MIN($current)", [$now, $now])
                ->whereColumn('product_id', 'products.id')
                ->where('is_active', true)])
            ->addSelect(['max_discount' => ProductVariant::query()
                ->selectRaw("MAX(price - ($current))", [$now, $now])
                ->whereColumn('product_id', 'products.id')
                ->where('is_active', true)])
            ->when($filters['sort'] === 'newest', fn ($q) => $q->latest('products.id'))
            ->when($filters['sort'] === 'price_asc', fn ($q) => $q->orderBy('min_price'))
            ->when($filters['sort'] === 'price_desc', fn ($q) => $q->orderByDesc('min_price'))
            ->when($filters['sort'] === 'discount', fn ($q) => $q->orderByDesc('max_discount'))
            ->with(['brand', 'variants', 'media']);
    }
}
