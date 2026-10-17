<?php

/**
 * Khaneh Romantic — Checkout routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;

Route::view('cart', 'checkout::cart')->name('checkout.cart');
