<?php

/**
 * Khaneh Romantic — App-level routes (module routes live in modules/{Name}/routes)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
