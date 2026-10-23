<?php

/**
 * Khaneh Romantic — called by the service worker (no session, no CSRF): the browser replaced its
 * subscription; knowing the old endpoint is the proof
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Push\Http\Controllers\PushController;

Route::post('push/renew', [PushController::class, 'renew'])->middleware('throttle:20,1')->name('push.renew');
