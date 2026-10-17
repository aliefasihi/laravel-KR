<?php

/**
 * Khaneh Romantic — WordPress import settings (merged as config('modules.wordpressimport'))
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [

    // Read-only source database of the current WooCommerce site.
    'connection' => [
        'driver' => 'mysql',
        'host' => env('WP_DB_HOST', '127.0.0.1'),
        'port' => env('WP_DB_PORT', '3306'),
        'database' => env('WP_DB_DATABASE', 'wordpress'),
        'username' => env('WP_DB_USERNAME', 'root'),
        'password' => env('WP_DB_PASSWORD', ''),
        'prefix' => env('WP_DB_PREFIX', 'wp_'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'strict' => false,
    ],

    // Base URL of the live site, used to recognize internal links inside content.
    'legacy_base_url' => env('WP_BASE_URL', 'https://khanehromantic.com'),

];
