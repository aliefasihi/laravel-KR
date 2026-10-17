<?php

/**
 * Khaneh Romantic — Category page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Catalog\Models\Category;

class CategoryController
{
    public function show(string $path): View
    {
        $category = Category::query()->byPath($path)->where('is_active', true)->firstOrFail();

        $products = $category->products()
            ->active()
            ->with(['brand', 'variants'])
            ->orderBy('category_product.position')
            ->paginate(24)
            ->withPath($category->url()); // keep the trailing slash in ?page= links (no extra 301)

        return view('catalog::category.show', compact('category', 'products'));
    }
}
