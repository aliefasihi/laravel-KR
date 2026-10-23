<?php

/**
 * Khaneh Romantic — Web push routes (subscribe from the page; campaign click tracking)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Push\Http\Controllers\PushController;

Route::prefix('push')->name('push.')->group(function () {
    Route::post('subscribe', [PushController::class, 'subscribe'])->middleware('throttle:20,1')->name('subscribe');
    Route::post('unsubscribe', [PushController::class, 'unsubscribe'])->middleware('throttle:20,1')->name('unsubscribe');
    Route::get('c/{campaign}', [PushController::class, 'click'])->whereNumber('campaign')->name('click');
});
