<?php

/**
 * Khaneh Romantic — «Bring my cart back» link from the reminder SMS
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\AbandonedCart\Http\Controllers\RestoreCartController;

Route::get('cart/back/{token}', RestoreCartController::class)->where('token', '[A-Za-z0-9]{24}')->middleware('throttle:30,1')->name('abandoned.restore');
