<?php

/**
 * Khaneh Romantic — Category page with GET filters (crawlable, no JS required)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class CategoryController
{
    public const SORTS = [
        'newest' => 'جدیدترین',
        'price_asc' => 'ارزان‌ترین',
        'price_desc' => 'گران‌ترین',
    ];

    public function show(Request $request, string $path): View
    {
        $category = Category::query()->byPath($path)->where('is_active', true)
            ->with(['parent', 'children' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();

        $categoryIds = $category->descendantIds();

        $filters = [
            'brand' => array_values(array_filter((array) $request->query('brand', []), 'is_string')),
            'in_stock' => $request->boolean('in_stock'),
            'sort' => array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'newest',
        ];

        $products = Product::query()
            ->active()
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->when($filters['brand'], fn ($q, $slugs) => $q->whereHas('brand', fn ($b) => $b->whereIn('slug', $slugs)))
            ->when($filters['in_stock'], fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock', '>', 0)))
            ->addSelect(['min_price' => ProductVariant::query()
                ->selectRaw('MIN(price)')
                ->whereColumn('product_id', 'products.id')
                ->where('is_active', true)])
            ->when($filters['sort'] === 'newest', fn ($q) => $q->latest('products.id'))
            ->when($filters['sort'] === 'price_asc', fn ($q) => $q->orderBy('min_price'))
            ->when($filters['sort'] === 'price_desc', fn ($q) => $q->orderByDesc('min_price'))
            ->with(['brand', 'variants', 'media'])
            ->paginate(24)
            ->withPath($category->url())   // keeps the trailing slash in ?page= links (no extra 301)
            ->withQueryString();

        $brands = Brand::query()
            ->whereHas('products', fn ($q) => $q->active()->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds)))
            ->orderBy('name_fa')
            ->get();

        $filtered = $filters['brand'] !== [] || $filters['in_stock'] || $filters['sort'] !== 'newest';

        return view('catalog::category.show', [
            'category' => $category,
            'products' => $products,
            'brands' => $brands,
            'filters' => $filters,
            'filtered' => $filtered,
            'sorts' => self::SORTS,
        ]);
    }
}
