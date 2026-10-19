<?php

/**
 * Khaneh Romantic — Content routes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\NewsletterController;
use Modules\Content\Http\Controllers\PageController;
use Modules\Content\Http\Controllers\PostController;

$prefix = config('khaneh.urls.blog_prefix');

Route::get($prefix, [PostController::class, 'index'])->name('content.blog');

Route::get($prefix.'/{slug}', [PostController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('content.post');

// Same slugs as the WordPress site (/aboutus/, /contact-us/ → one 301 to the slash-less form).
Route::get('aboutus', [PageController::class, 'about'])->name('pages.about');
Route::get('contact-us', [PageController::class, 'contact'])->name('pages.contact');

Route::post('newsletter', NewsletterController::class)
    ->middleware('throttle:5,1')
    ->name('newsletter.subscribe');
Route::get('newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('newsletter.unsubscribe');
