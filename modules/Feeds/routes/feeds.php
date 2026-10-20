<?php

/**
 * Khaneh Romantic — Price-comparison feeds (no session, no CSRF: the api middleware group)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   POST /torob_api/v3/products   Torob Product API v3 (JWT signed by Torob)
 *   GET|POST /emalls/products     Emalls web service (token checked with emalls.ir)
 */

use Illuminate\Support\Facades\Route;
use Modules\Feeds\Http\Controllers\EmallsController;
use Modules\Feeds\Http\Controllers\TorobController;

Route::post('torob_api/v3/products', TorobController::class)->middleware('throttle:120,1')->name('feeds.torob');
Route::match(['get', 'post'], 'emalls/products', EmallsController::class)->middleware('throttle:120,1')->name('feeds.emalls');
