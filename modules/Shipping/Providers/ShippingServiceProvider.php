<?php

/**
 * Khaneh Romantic — Shipping module
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Prices shipping in the checkout pricing pipeline, after discounts (so «free over X» counts the
 * discounted cart, and a free-shipping coupon zeroes it).
 */

namespace Modules\Shipping\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Checkout\Support\Pricing\Quoter;
use Modules\Shipping\Support\ShippingOptions;

class ShippingServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(ShippingOptions::class);
    }

    public function boot(): void
    {
        parent::boot();

        Quoter::extend('shipping', fn (QuoteContext $context, Quote $quote) => app(ShippingOptions::class)->apply($context, $quote), priority: 20);
    }
}
