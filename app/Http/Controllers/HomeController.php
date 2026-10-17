<?php

/**
 * Khaneh Romantic — Home page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Content\Models\Post;

class HomeController
{
    public function __invoke(): View
    {
        return view('home', [
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort')
                ->limit(8)
                ->get(),
            'products' => Product::query()->active()->with(['brand', 'variants', 'media'])->latest('id')->limit(8)->get(),
            'brands' => Brand::query()->withCount(['products' => fn ($q) => $q->active()])->orderByDesc('products_count')->get(),
            'posts' => Post::query()->published()->latest('published_at')->limit(3)->get(),
        ]);
    }
}
