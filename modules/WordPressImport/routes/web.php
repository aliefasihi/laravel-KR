<?php

/**
 * Khaneh Romantic — WordPress import routes (admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\WordPressImport\Http\Controllers\DumpUploadController;

Route::post('admin/wordpress-import/upload', DumpUploadController::class)
    ->middleware('auth')
    ->name('wordpressimport.upload');
