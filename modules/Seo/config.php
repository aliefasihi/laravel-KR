<?php

/**
 * Khaneh Romantic — SEO module settings → config('modules.seo')
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [

    /*
     | Path prefixes of product pages on the old WordPress site (WooCommerce "product base").
     | A 404 under one of these is matched against the catalog and a redirect is suggested.
     */
    'legacy_product_prefixes' => ['product', 'محصول'],

    /** How much of the old URL's words must be found in a product (0..1) to suggest it. */
    'suggest_min_score' => (float) env('SEO_SUGGEST_MIN_SCORE', 0.6),

    /**
     | Above this score the 301 is created immediately, without review (null = always review).
     | A SKU found in the old URL scores 1.0, so 1.0 means "only automatic on an exact SKU match".
     */
    'auto_redirect_score' => filled(env('SEO_AUTO_REDIRECT_SCORE')) ? (float) env('SEO_AUTO_REDIRECT_SCORE') : null,

];
