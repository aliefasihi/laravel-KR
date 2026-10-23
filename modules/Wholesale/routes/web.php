<?php

/**
 * Khaneh Romantic — Wholesale routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Wholesale\Http\Controllers\WholesaleController;

Route::get('wholesale', [WholesaleController::class, 'show'])->name('wholesale');
Route::get('wholesale/document/{account}', [WholesaleController::class, 'document'])->middleware('signed')->name('wholesale.document');
