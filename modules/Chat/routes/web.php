<?php

/**
 * Khaneh Romantic — Site chat endpoints (called by resources/js/chat.js)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ChatController;

Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('history', [ChatController::class, 'history'])->middleware('throttle:30,1')->name('history');
    Route::post('send', [ChatController::class, 'send'])->middleware('throttle:20,1')->name('send');
});
