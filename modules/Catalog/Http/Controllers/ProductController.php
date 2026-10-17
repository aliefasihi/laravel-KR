<?php

/**
 * Khaneh Romantic — Product page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Catalog\Models\Product;

class ProductController
{
    public function show(string $slug): View|RedirectResponse
    {
        $canonical = strtolower($slug);

        if ($canonical !== $slug) {
            return redirect()->route('catalog.product', $canonical, 301);
        }

        // Missing slug → 404 → Seo module checks the redirects table (old SKU/brand slugs).
        $product = Product::query()
            ->active()
            ->where('slug', $canonical)
            ->with(['brand', 'categories.parent', 'variants', 'media'])
            ->firstOrFail();

        $category = $product->primaryCategory();

        $related = $category === null ? collect() : Product::query()
            ->active()
            ->whereKeyNot($product->id)
            ->whereHas('categories', fn ($q) => $q->whereKey($category->id))
            ->with(['brand', 'variants', 'media'])
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('catalog::product.show', compact('product', 'category', 'related'));
    }
}
