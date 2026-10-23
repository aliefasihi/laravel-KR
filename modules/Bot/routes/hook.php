<?php

/**
 * Khaneh Romantic — Bot webhooks (called by Telegram/Bale: no session, no CSRF; the secret is in the address)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Bot\Http\Controllers\WebhookController;

Route::post('bot/hook/{channel}/{secret}', WebhookController::class)->whereIn('channel', ['telegram', 'bale'])->middleware('throttle:120,1')->name('bot.hook');
