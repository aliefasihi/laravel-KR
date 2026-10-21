<?php

/**
 * Khaneh Romantic — Payment routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\ReceiptController;
use Modules\Payment\Http\Controllers\SnappPayController;
use Modules\Payment\Http\Controllers\ZarinpalController;

Route::get('payment/zarinpal/callback', [ZarinpalController::class, 'callback'])->name('payment.zarinpal.callback');
Route::get('payment/receipts/{payment}', [ReceiptController::class, 'show'])->middleware('signed')->name('payment.receipt');

// SnappPay posts back from the shopper's browser (cross-site: CSRF-exempt in bootstrap/app.php)
Route::post('payment/snapppay/callback', [SnappPayController::class, 'callback'])->name('payment.snapppay.callback');
Route::get('payment/snapppay/done/{payment}', [SnappPayController::class, 'done'])->middleware('signed')->name('payment.snapppay.done');
