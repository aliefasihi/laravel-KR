<?php

/**
 * Khaneh Romantic — Search module settings → config('modules.search')
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [

    /*
     | Engine: database (no server, good up to a few thousand products),
     | meilisearch (light: ~100–300 MB RAM, typo tolerance out of the box) or
     | elasticsearch (heavy: 2 GB+ RAM, needed only for very large catalogs / analytics).
     | If the engine is unreachable, pages silently fall back to the database engine.
     */
    'driver' => env('SEARCH_DRIVER', 'database'),

    'index' => env('SEARCH_INDEX', 'khaneh_products'),

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),
    ],

    'elasticsearch' => [
        'host' => env('ELASTICSEARCH_HOST', 'http://127.0.0.1:9200'),
        'username' => env('ELASTICSEARCH_USERNAME'),
        'password' => env('ELASTICSEARCH_PASSWORD'),
        'api_key' => env('ELASTICSEARCH_API_KEY'),
    ],

    /** Seconds: a slow engine must never make a page slow (we fall back instead). */
    'timeout' => (float) env('SEARCH_TIMEOUT', 1.5),

    /** Record what visitors search for (admin → «گزارش جستجو»). */
    'log_terms' => env('SEARCH_LOG_TERMS', true),

    /*
     | Words shoppers use interchangeably. Each line is a group of equivalents.
     | Written in normal Persian; normalization (ی/ک، نیم‌فاصله، ارقام) is applied automatically.
     | After changing this list run: php artisan search:rebuild
     */
    'synonyms' => [
        ['قابلمه', 'دیگ'],
        ['تابه', 'ماهیتابه'],
        ['سرویس غذاخوری', 'ظروف غذاخوری'],
        ['قاشق چنگال', 'قاشق و چنگال'],
        ['کارد', 'چاقو'],
        ['آبکش', 'صافی'],
        ['بانکه', 'ظرف نگهدارنده'],
        ['جا ادویه', 'جاادویه', 'ادویه دان'],
        ['شمعدان', 'جاشمعی', 'جا شمعی'],
        ['چای ساز', 'چایساز'],
        ['قهوه ساز', 'قهوه جوش'],
        ['زودپز', 'زود پز'],
        ['آرکوپال', 'اوپال'],
        ['کریستال', 'بلور'],
        ['استیل', 'استنلس'],
        ['تفلون', 'نچسب'],
        ['گرانیتی', 'گرانیت'],
        ['جهیزیه', 'جهاز'],
        ['ماگ', 'لیوان دسته دار'],
        ['گلدکیش', 'گلد کیش', 'goldkish'],
        ['ریتون', 'riton', 'rhyton'],   // the old site's brand slug is "rhyton"
    ],

];
