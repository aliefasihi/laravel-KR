<?php

/**
 * Khaneh Romantic — Catalog routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * /product-category/{path}/  ← preserved WordPress URL, trailing slash kept
 * /p/{brand}-{sku}           ← new product URL, ASCII only
 * /brand/{slug}
 */

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\BrandController;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\ProductController;

$urls = config('khaneh.urls');

Route::get($urls['category_prefix'].'/{path}', [
    'uses' => [CategoryController::class, 'show'],
    'trailing_slash' => true,
])->where('path', '.+')->name('catalog.category');

// Mixed case is accepted only to 301 it to the lowercase canonical.
Route::get($urls['product_prefix'].'/{slug}', [ProductController::class, 'show'])
    ->where('slug', '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*')
    ->name('catalog.product');

Route::get($urls['brand_prefix'].'/{slug}', [BrandController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('catalog.brand');
