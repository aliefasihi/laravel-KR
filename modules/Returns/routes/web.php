<?php

/**
 * Khaneh Romantic — Returns routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Returns\Http\Controllers\ReturnController;

Route::middleware(AuthenticateCustomer::class)->prefix('account')->name('customer.')->group(function () {
    Route::get('returns', [ReturnController::class, 'index'])->name('returns');
    Route::get('orders/{order}/return', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('returns/{request}/cancel', [ReturnController::class, 'cancel'])->name('returns.cancel');
});

Route::get('returns/photo/{request}/{index}', [ReturnController::class, 'photo'])->middleware('signed')->whereNumber('index')->name('returns.photo');
