<?php

/**
 * Khaneh Romantic — Store-wide settings
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * URL policy: see docs/URL-POLICY.md
 */

return [

    'urls' => [
        // Preserved 1:1 from WordPress (incl. trailing slash). Change only if the live site differs.
        'category_prefix' => env('KHANEH_CATEGORY_PREFIX', 'product-category'),

        // New, ASCII-only URLs.
        'product_prefix' => 'p',     // /p/{brand}-{sku}
        'blog_prefix' => 'blog',     // /blog/{slug}
        'brand_prefix' => 'brand',   // /brand/{slug}
    ],

    // Timestamps are stored in UTC (config/app.php); shown to customers in Tehran time.
    'display_timezone' => 'Asia/Tehran',

    // Money is stored as integer Rials everywhere; this only affects display.
    'currency' => [
        'storage' => 'IRR',
        'display' => 'IRT', // تومان
    ],

];
