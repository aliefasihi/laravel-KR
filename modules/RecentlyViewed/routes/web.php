<?php

/**
 * Khaneh Romantic — «Recently viewed» row, filled in the browser (ids from localStorage)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\RecentlyViewed\Http\Controllers\RecentlyViewedController;

Route::get('recently-viewed', RecentlyViewedController::class)->middleware('throttle:60,1')->name('recent.rail');
