<?php

/**
 * Khaneh Romantic — Catalog routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * /{category-path}          ← categories at the site root (checked after every other route)
 * /p/{brand}-{sku}           ← product URL, ASCII only
 * /brand/{slug}
 * /shop                     ← all products (same slug as WordPress)
 * /search?q=
 */

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\BrandController;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\SearchController;
use Modules\Catalog\Http\Controllers\ShopController;

$urls = config('khaneh.urls');

// With no prefix the category route is a fallback: Laravel tries it only after every other route.
$category = $urls['category_prefix'] === ''
    ? Route::get('{path}', [CategoryController::class, 'show'])->fallback()
    : Route::get($urls['category_prefix'].'/{path}', [CategoryController::class, 'show']);
$category->where('path', '[^/].*')->name('catalog.category');

// Mixed case is accepted only to 301 it to the lowercase canonical.
Route::get($urls['product_prefix'].'/{slug}', [ProductController::class, 'show'])
    ->where('slug', '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*')
    ->name('catalog.product');

Route::get($urls['brand_prefix'].'/{slug}', [BrandController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('catalog.brand');

Route::get('shop', ShopController::class)->name('catalog.shop');

Route::get('search', SearchController::class)->name('catalog.search');
