<?php

/**
 * Khaneh Romantic — Order routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Order\Http\Controllers\OrderController;
use Modules\Order\Http\Controllers\PrintController;

Route::middleware(AuthenticateCustomer::class)->group(function () {
    Route::get('checkout', [OrderController::class, 'checkout'])->name('order.checkout');

    Route::prefix('account/orders')->name('customer.orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('');
        Route::get('{order}', [OrderController::class, 'show'])->name('.show');
        Route::post('{order}/pay', [OrderController::class, 'pay'])->name('.pay');
        Route::post('{order}/cancel', [OrderController::class, 'cancel'])->name('.cancel');
        Route::get('{order}/invoice', [PrintController::class, 'customer'])->name('.invoice');
    });
});

// invoices / packing slips / shipping labels: signed links generated in the admin (OrderActions::printUrl)
Route::get('orders/print', PrintController::class)->middleware('signed')->name('order.print');
