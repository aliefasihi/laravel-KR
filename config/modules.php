<?php

use Modules\Appearance\Providers\AppearanceServiceProvider;
use Modules\Attributes\Providers\AttributesServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Checkout\Providers\CheckoutServiceProvider;
use Modules\Comments\Providers\CommentsServiceProvider;
use Modules\Content\Providers\ContentServiceProvider;
use Modules\Coupon\Providers\CouponServiceProvider;
use Modules\Crm\Providers\CrmServiceProvider;
use Modules\Customer\Providers\CustomerServiceProvider;
use Modules\FileManager\Providers\FileManagerServiceProvider;
use Modules\HomeSliders\Providers\HomeSlidersServiceProvider;
use Modules\Integration\Providers\IntegrationServiceProvider;
use Modules\Media\Providers\MediaServiceProvider;
use Modules\Order\Providers\OrderServiceProvider;
use Modules\Payment\Providers\PaymentServiceProvider;
use Modules\Placement\Providers\PlacementServiceProvider;
use Modules\Reviews\Providers\ReviewsServiceProvider;
use Modules\Search\Providers\SearchServiceProvider;
use Modules\Seo\Providers\SeoServiceProvider;
use Modules\Shipping\Providers\ShippingServiceProvider;
use Modules\Sms\Providers\SmsServiceProvider;
use Modules\WordPressImport\Providers\WordPressImportServiceProvider;

/**
 * Khaneh Romantic — Installed modules (order = boot order).
 * Which of them are switched on is decided in the admin: تنظیمات ← ماژول‌ها.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [

    'enabled' => [
        MediaServiceProvider::class,
        SeoServiceProvider::class,
        CatalogServiceProvider::class,
        SearchServiceProvider::class,
        FileManagerServiceProvider::class,
        AttributesServiceProvider::class,
        ContentServiceProvider::class,
        AppearanceServiceProvider::class,
        HomeSlidersServiceProvider::class,
        PlacementServiceProvider::class,
        SmsServiceProvider::class,
        CustomerServiceProvider::class,
        CrmServiceProvider::class,
        CheckoutServiceProvider::class,
        CouponServiceProvider::class,
        ShippingServiceProvider::class,
        OrderServiceProvider::class,
        PaymentServiceProvider::class,
        ReviewsServiceProvider::class,
        CommentsServiceProvider::class,
        IntegrationServiceProvider::class,
        WordPressImportServiceProvider::class,
    ],

];
