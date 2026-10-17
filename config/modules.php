<?php

use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Checkout\Providers\CheckoutServiceProvider;
use Modules\Content\Providers\ContentServiceProvider;
use Modules\Customer\Providers\CustomerServiceProvider;
use Modules\Integration\Providers\IntegrationServiceProvider;
use Modules\Media\Providers\MediaServiceProvider;
use Modules\Order\Providers\OrderServiceProvider;
use Modules\Payment\Providers\PaymentServiceProvider;
use Modules\Placement\Providers\PlacementServiceProvider;
use Modules\Seo\Providers\SeoServiceProvider;
use Modules\WordPressImport\Providers\WordPressImportServiceProvider;

/**
 * Khaneh Romantic — Enabled modules (order = boot order)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [

    'enabled' => [
        MediaServiceProvider::class,
        SeoServiceProvider::class,
        CatalogServiceProvider::class,
        ContentServiceProvider::class,
        PlacementServiceProvider::class,
        CustomerServiceProvider::class,
        CheckoutServiceProvider::class,
        OrderServiceProvider::class,
        PaymentServiceProvider::class,
        IntegrationServiceProvider::class,
        WordPressImportServiceProvider::class,
    ],

];
