<?php

/**
 * Khaneh Romantic — Store API v1 (prefix: /api/v1, name: api.v1.)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Next: `php artisan install:api` (Sanctum) and token abilities per client:
 *   catalog:read, content:write, banners:write, orders:read
 */

use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => ['service' => 'khanehromantic-store', 'api' => 'v1'])->name('ping');
