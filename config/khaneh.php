<?php

/**
 * Khaneh Romantic — Store-wide settings
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * URL policy: see docs/URL-POLICY.md
 */

return [

    'urls' => [
        // Categories live at the site root: /tableware/servingware/nut-bowls (empty prefix). Any other
        // route wins over a category; a top-level category can't take a route's name (Category::saving).
        'category_prefix' => trim((string) env('KHANEH_CATEGORY_PREFIX', ''), '/'),

        // New, ASCII-only URLs.
        'product_prefix' => 'p',     // /p/{brand}-{sku}
        'blog_prefix' => 'blog',     // /blog/{slug}
        'brand_prefix' => 'brand',   // /brand/{slug}
    ],

    // Storefront identity, contact details and trust badges (footer, header, schema.org).
    'store' => [
        'name' => 'خانه رمانتیک',
        'name_en' => 'Khaneh Romantic',
        'tagline' => 'مرجع تخصصی لوازم و اکسسوری خانه و آشپزخانه',
        'about' => 'خانه رمانتیک با هدف خلق فضایی زیبا، گرم و دلنشین برای خانه‌های ایرانی شکل گرفته است؛ از ظروف آشپزخانه و سرو و پذیرایی تا اکسسوری‌های دکوراتیو، با برندهایی که به کیفیتشان اطمینان داریم.',
        'phone' => env('KHANEH_PHONE', '02140550624'),
        'mobile' => env('KHANEH_MOBILE', '09121503002'),
        'email' => env('KHANEH_EMAIL', 'info@khanehromantic.com'),
        'address' => 'تهران، میدان شوش، خیابان صابونیان، مجتمع تجاری رضایی، طبقه زیرهمکف، پلاک ۴۷',
        'whatsapp' => env('KHANEH_WHATSAPP', '989121503002'),   // international format, no +
        'instagram' => env('KHANEH_INSTAGRAM'),                  // full URL
        'telegram' => env('KHANEH_TELEGRAM'),                    // full URL
        'enamad_url' => env('KHANEH_ENAMAD_URL', 'https://trustseal.enamad.ir/?id=520136&Code=6DS1v2BMw3PW0cOtdHF8tuJwFsioTmQ5'),
        'warranty' => 'گارانتی اصالت کالا',
    ],

    // Top announcement bar (empty string hides it).
    'promo' => env('KHANEH_PROMO', '۱۰٪ تخفیف برای اولین سفارش'),

    // Storefront font: "vazirmatn" (bundled, free) or "kalameh" (licensed — drop the
    // woff2 files into public/fonts/kalameh/ first, see public/fonts/kalameh/README.md).
    'font' => env('KHANEH_FONT', 'vazirmatn'),

    // Logo: put the brand logo at public/images/logo.webp (falls back to the wordmark).
    'logo' => 'images/logo.webp',

    // Timestamps are stored in UTC (config/app.php); shown to customers in Tehran time.
    'display_timezone' => 'Asia/Tehran',

    // Money is stored as integer Rials everywhere; this only affects display.
    'currency' => [
        'storage' => 'IRR',
        'display' => 'IRT', // تومان
    ],

];
