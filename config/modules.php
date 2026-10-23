<?php

use Modules\AbandonedCart\Providers\AbandonedCartServiceProvider;
use Modules\Ai\Providers\AiServiceProvider;
use Modules\Appearance\Providers\AppearanceServiceProvider;
use Modules\Attributes\Providers\AttributesServiceProvider;
use Modules\Bot\Providers\BotServiceProvider;
use Modules\BulkEdit\Providers\BulkEditServiceProvider;
use Modules\Bundles\Providers\BundlesServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Chat\Providers\ChatServiceProvider;
use Modules\Checkout\Providers\CheckoutServiceProvider;
use Modules\Comments\Providers\CommentsServiceProvider;
use Modules\Compare\Providers\CompareServiceProvider;
use Modules\Content\Providers\ContentServiceProvider;
use Modules\Coupon\Providers\CouponServiceProvider;
use Modules\Crm\Providers\CrmServiceProvider;
use Modules\Customer\Providers\CustomerServiceProvider;
use Modules\ExitPopup\Providers\ExitPopupServiceProvider;
use Modules\Feeds\Providers\FeedsServiceProvider;
use Modules\FileManager\Providers\FileManagerServiceProvider;
use Modules\GiftWrap\Providers\GiftWrapServiceProvider;
use Modules\HomeSliders\Providers\HomeSlidersServiceProvider;
use Modules\Integration\Providers\IntegrationServiceProvider;
use Modules\Inventory\Providers\InventoryServiceProvider;
use Modules\Loyalty\Providers\LoyaltyServiceProvider;
use Modules\Media\Providers\MediaServiceProvider;
use Modules\Occasions\Providers\OccasionsServiceProvider;
use Modules\Order\Providers\OrderServiceProvider;
use Modules\Payment\Providers\PaymentServiceProvider;
use Modules\Placement\Providers\PlacementServiceProvider;
use Modules\Push\Providers\PushServiceProvider;
use Modules\Pwa\Providers\PwaServiceProvider;
use Modules\Questions\Providers\QuestionsServiceProvider;
use Modules\RecentlyViewed\Providers\RecentlyViewedServiceProvider;
use Modules\Registry\Providers\RegistryServiceProvider;
use Modules\Returns\Providers\ReturnsServiceProvider;
use Modules\Reviews\Providers\ReviewsServiceProvider;
use Modules\Search\Providers\SearchServiceProvider;
use Modules\Seo\Providers\SeoServiceProvider;
use Modules\Shipping\Providers\ShippingServiceProvider;
use Modules\Sms\Providers\SmsServiceProvider;
use Modules\Tracking\Providers\TrackingServiceProvider;
use Modules\Wholesale\Providers\WholesaleServiceProvider;
use Modules\Wishlist\Providers\WishlistServiceProvider;
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
        LoyaltyServiceProvider::class,
        ReviewsServiceProvider::class,
        CommentsServiceProvider::class,
        WishlistServiceProvider::class,
        AbandonedCartServiceProvider::class,
        RegistryServiceProvider::class,
        BundlesServiceProvider::class,
        GiftWrapServiceProvider::class,
        RecentlyViewedServiceProvider::class,
        InventoryServiceProvider::class,
        PwaServiceProvider::class,
        OccasionsServiceProvider::class,
        TrackingServiceProvider::class,
        BulkEditServiceProvider::class,
        CompareServiceProvider::class,
        QuestionsServiceProvider::class,
        ReturnsServiceProvider::class,
        WholesaleServiceProvider::class,
        ExitPopupServiceProvider::class,
        PushServiceProvider::class,
        AiServiceProvider::class,
        BotServiceProvider::class,
        ChatServiceProvider::class,
        IntegrationServiceProvider::class,
        FeedsServiceProvider::class,
        WordPressImportServiceProvider::class,
    ],

];
