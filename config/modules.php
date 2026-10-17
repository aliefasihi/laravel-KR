<?php

/**
 * Khaneh Romantic — Enabled modules (order = boot order)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [

    'enabled' => [
        Modules\Media\Providers\MediaServiceProvider::class,
        Modules\Seo\Providers\SeoServiceProvider::class,
        Modules\Catalog\Providers\CatalogServiceProvider::class,
        Modules\Content\Providers\ContentServiceProvider::class,
        Modules\Placement\Providers\PlacementServiceProvider::class,
        Modules\Customer\Providers\CustomerServiceProvider::class,
        Modules\Checkout\Providers\CheckoutServiceProvider::class,
        Modules\Order\Providers\OrderServiceProvider::class,
        Modules\Payment\Providers\PaymentServiceProvider::class,
        Modules\Integration\Providers\IntegrationServiceProvider::class,
        Modules\WordPressImport\Providers\WordPressImportServiceProvider::class,
    ],

];
