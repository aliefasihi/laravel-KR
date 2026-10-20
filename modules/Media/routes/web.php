<?php

/**
 * Khaneh Romantic — Media routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\StorageFileController;

Route::get('storage/{path}', StorageFileController::class)->where('path', '.+')->name('media.file');
