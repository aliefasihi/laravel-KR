<?php

/**
 * Khaneh Romantic — Customer routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\AccountController;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;

Route::get('account/login', [AccountController::class, 'login'])->name('customer.login');

Route::middleware(AuthenticateCustomer::class)->prefix('account')->name('customer.')->group(function () {
    Route::get('/', [AccountController::class, 'show'])->name('account');
    Route::get('addresses', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('logout', [AccountController::class, 'logout'])->name('logout');
});
