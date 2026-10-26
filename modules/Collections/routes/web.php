<?php

/**
 * Khaneh Romantic — Collections: /collections, /collections/{slug}
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Collections\Http\Controllers\CollectionController;

Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('collections/{slug}', [CollectionController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('collections.show');
