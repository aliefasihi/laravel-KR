<?php

/**
 * Khaneh Romantic — Payment routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\ReceiptController;
use Modules\Payment\Http\Controllers\ZarinpalController;

Route::get('payment/zarinpal/callback', [ZarinpalController::class, 'callback'])->name('payment.zarinpal.callback');
Route::get('payment/receipts/{payment}', [ReceiptController::class, 'show'])->middleware('signed')->name('payment.receipt');
