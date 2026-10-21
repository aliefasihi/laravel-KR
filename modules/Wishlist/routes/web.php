<?php

/**
 * Khaneh Romantic — Wishlist pages (in the customer account)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Wishlist\Http\Controllers\WishlistController;

Route::middleware(AuthenticateCustomer::class)->prefix('account')->name('customer.')->group(function () {
    Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::delete('wishlist/{item}', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::delete('stock-alerts/{alert}', [WishlistController::class, 'cancelAlert'])->name('stock-alerts.cancel');
});
