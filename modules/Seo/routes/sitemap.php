<?php

/**
 * Khaneh Romantic — sitemaps and robots.txt (no session, no cookies)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Seo\Http\Controllers\SitemapController;

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('seo.sitemap');
Route::get('sitemap_index.xml', [SitemapController::class, 'legacy']);
Route::get('sitemap-products-{page}.xml', [SitemapController::class, 'products'])->whereNumber('page');
Route::get('sitemap-{type}.xml', [SitemapController::class, 'part'])->whereIn('type', ['pages', 'categories', 'brands', 'posts']);
Route::get('robots.txt', [SitemapController::class, 'robots']);
