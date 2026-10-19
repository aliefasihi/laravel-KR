<?php

/**
 * Khaneh Romantic — Customer club routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;

Route::middleware(AuthenticateCustomer::class)->prefix('account')->name('customer.')->group(function () {
    Route::view('club', 'loyalty::account.club')->name('club');
});
