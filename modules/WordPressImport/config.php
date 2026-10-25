<?php

/**
 * Khaneh Romantic — WordPress import settings (merged as config('modules.wordpressimport'))
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [

    // Read-only source database of the current WooCommerce site.
    'connection' => [
        'driver' => env('WP_DB_DRIVER', 'mysql'),
        'host' => env('WP_DB_HOST', '127.0.0.1'),
        'port' => env('WP_DB_PORT', '3306'),
        'database' => env('WP_DB_DATABASE', 'wordpress'),
        'username' => env('WP_DB_USERNAME', 'root'),
        'password' => env('WP_DB_PASSWORD', ''),
        'prefix' => env('WP_DB_PREFIX', 'wp_'),
        // Local by Flywheel / MAMP use a socket file instead of 127.0.0.1:3306
        'unix_socket' => env('WP_DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'strict' => false,
    ],

    // Base URL of the live site: internal links in content, and where images are downloaded from.
    'legacy_base_url' => env('WP_BASE_URL', 'https://khanehromantic.com'),

    // The old wp-content/uploads folder, if it is on this server (faster than downloading).
    'uploads_path' => env('WP_UPLOADS_PATH'),

    // WooCommerce currency: IRT (Toman, multiplied by 10) or IRR (Rial, as is).
    'currency' => env('WP_CURRENCY', 'IRT'),

    // Brand taxonomy (first one found is used) and fixed ASCII slugs for Persian brand names.
    'brand_taxonomies' => ['product_brand', 'brand', 'pwb-brand', 'yith_product_brand', 'pa_brand'],
    'brand_slugs' => [
        // 'گلدکیش' => ['slug' => 'goldkish', 'name_en' => 'Goldkish'],
    ],
    'default_brand' => ['slug' => 'khanehromantic', 'name_fa' => 'خانه رمانتیک', 'name_en' => 'Khaneh Romantic'],

    // Products whose stock isn't managed in WooCommerce but are «in stock» get this quantity.
    'unmanaged_stock' => 50,

    // Product comments without stars become reviews with this rating (null = skip them).
    'review_default_rating' => 5,

    // WooCommerce order statuses → ours (others: processing). Custom statuses go here.
    'order_statuses' => [
        'wc-pending' => 'pending', 'wc-on-hold' => 'on_hold', 'wc-processing' => 'processing',
        'wc-completed' => 'completed', 'wc-cancelled' => 'cancelled', 'wc-refunded' => 'refunded',
        'wc-failed' => 'failed', 'wc-shipped' => 'shipped', 'wc-sent' => 'shipped', 'wc-ersal' => 'shipped',
    ],

    // Old WordPress pages → new addresses (decoded slugs).
    'page_redirects' => [
        'shop' => '/shop', 'فروشگاه' => '/shop', 'cart' => '/cart', 'سبد-خرید' => '/cart',
        'checkout' => '/checkout', 'my-account' => '/account', 'حساب-کاربری' => '/account',
        'about-us' => '/aboutus', 'درباره-ما' => '/aboutus', 'contact-us' => '/contact-us', 'تماس-با-ما' => '/contact-us',
        'blog' => '/blog', 'مجله' => '/blog',
        'home' => '/', 'newsletter' => '/', 'account-security' => '/account', 'wishlist' => '/account/wishlist',
        'returen-policy' => '/return-policy', 'privacy-policy' => '/privacy-policy',
        'compare' => '/compare', 'compare-2' => '/compare', 'yith-compare' => '/compare', 'ever-compare' => '/compare',
    ],

];
