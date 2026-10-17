<?php

/**
 * Khaneh Romantic — Content routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\PostController;

$prefix = config('khaneh.urls.blog_prefix');

Route::get($prefix, [PostController::class, 'index'])->name('content.blog');

Route::get($prefix.'/{slug}', [PostController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('content.post');
