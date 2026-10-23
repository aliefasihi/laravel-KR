<?php

/**
 * Khaneh Romantic — Compare page (/compare?ids=12,15,31 — the list lives in the visitor's browser)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Compare\Http\Controllers\CompareController;

Route::get('compare', CompareController::class)->name('compare');
