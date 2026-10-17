<?php

/**
 * Khaneh Romantic — Brand page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Catalog\Models\Brand;

class BrandController
{
    public function show(string $slug): View
    {
        $brand = Brand::query()->where('slug', $slug)->firstOrFail();
        $products = $brand->products()->active()->with('variants')->latest()->paginate(24);

        return view('catalog::brand.show', compact('brand', 'products'));
    }
}
