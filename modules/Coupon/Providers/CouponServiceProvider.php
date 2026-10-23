<?php

/**
 * Khaneh Romantic — Coupon module
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Plugs into the checkout pricing pipeline (discounts run before shipping) and adds the coupon
 * field to the cart and checkout. Uses are recorded when an order is placed and given back when
 * it is cancelled (events from the Order module).
 */

namespace Modules\Coupon\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\StorefrontHooks;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Checkout\Support\Pricing\Quoter;
use Modules\Coupon\Livewire\CouponField;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Support\CouponEvaluator;
use Modules\Coupon\Support\CouponUsages;

class CouponServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(CouponEvaluator::class);
        $this->app->scoped(CouponUsages::class);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('coupon.field', CouponField::class);
        StorefrontHooks::add('cart.summary', fn () => view('coupon::field'), 10);

        Quoter::extend('coupon', function (QuoteContext $context, Quote $quote) {
            if (! $context->couponCode) {
                return;
            }
            if ($blocked = $quote->meta['no_discounts'] ?? null) {   // e.g. wholesale prices don't stack with coupons
                $quote->messages['coupon'] = $blocked;

                return;
            }
            $coupon = Coupon::findByCode($context->couponCode);
            $result = app(CouponEvaluator::class)->evaluate($coupon, $context);
            if (! $result['ok']) {
                $quote->messages['coupon'] = $result['error'];

                return;
            }
            if ($result['discount'] > 0) {
                $quote->addDiscount('coupon', 'کد تخفیف '.$coupon->code, $result['discount']);
            }
            $quote->freeShipping = $quote->freeShipping || $result['free_shipping'];
            $quote->meta['coupon'] = ['id' => $coupon->id, 'code' => $coupon->code, 'discount' => $result['discount'], 'free_shipping' => $result['free_shipping']];
        }, priority: 10);

        // Order module events (string names: this module doesn't depend on Order)
        Event::listen('order.placed', function (array $order) {
            if (! empty($order['coupon_code'])) {
                app(CouponUsages::class)->record($order['coupon_code'], $order['id'], $order['customer_id'], $order['mobile'], (int) $order['discount']);
            }
        });
        Event::listen('order.cancelled', fn (array $order) => app(CouponUsages::class)->release($order['id']));
    }
}
