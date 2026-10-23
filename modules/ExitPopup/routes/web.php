<?php

/**
 * Khaneh Romantic — Exit popup endpoints (called by resources/js/exit-popup.js)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\ExitPopup\Http\Controllers\ExitPopupController;

Route::prefix('exit-popup')->name('exit-popup.')->middleware('throttle:30,1')->group(function () {
    Route::post('event', [ExitPopupController::class, 'event'])->name('event');
    Route::post('apply', [ExitPopupController::class, 'apply'])->name('apply');
    Route::post('lead', [ExitPopupController::class, 'lead'])->name('lead');
});
