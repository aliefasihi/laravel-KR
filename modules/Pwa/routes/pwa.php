<?php

/**
 * Khaneh Romantic — PWA routes: no session or cookies (the browser fetches them on its own)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Pwa\Http\Controllers\PwaController;

Route::get('manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('sw.js', [PwaController::class, 'worker'])->name('pwa.worker');
Route::get('offline', [PwaController::class, 'offline'])->name('pwa.offline');
