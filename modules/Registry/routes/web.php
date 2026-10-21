<?php

/**
 * Khaneh Romantic — Wedding list routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Registry\Http\Controllers\RegistryController;

Route::get('list/{token}', [RegistryController::class, 'show'])->where('token', '[a-z0-9]{10}')->name('registry.show');
Route::get('account/registry', [RegistryController::class, 'manage'])->middleware(AuthenticateCustomer::class)->name('customer.registry');
