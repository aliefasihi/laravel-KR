<?php

/**
 * Khaneh Romantic — Search routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The full results page lives in Catalog (/search); this module adds the JSON suggestions
 * endpoint. It needs no session, cookies or CSRF, so those middleware are skipped.
 */

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Search\Http\Controllers\SuggestController;

Route::get('search/suggest', SuggestController::class)
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, VerifyCsrfToken::class, EncryptCookies::class, AddQueuedCookiesToResponse::class])
    ->middleware('throttle:120,1')
    ->name('search.suggest');
