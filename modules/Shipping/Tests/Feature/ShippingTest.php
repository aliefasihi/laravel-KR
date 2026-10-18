<?php

/**
 * Shipping options per address, province prices, free-over, pay on delivery, free-shipping coupons.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Shipping\Tests\Feature;

use Modules\Checkout\Support\Cart;
use Modules\Coupon\Models\Coupon;
use Modules\Order\Tests\Feature\ShopTestCase;

class ShippingTest extends ShopTestCase
{
    private function shippingOptions(string $province, string $city, ?string $method = null): array
    {
        $quote = app(Cart::class)->quote(['province' => $province, 'city' => $city, 'shippingMethod' => $method]);

        return [collect($quote->meta['shipping_options'] ?? [])->pluck('cost', 'code')->all(), $quote];
    }

    public function test_default_methods_by_address(): void
    {
        app(Cart::class)->add($this->variant('3217073')->id, 1); // 29,500,000 Rials

        [$tehran] = $this->shippingOptions('تهران', 'تهران');
        $this->assertSame(['post' => 600_000, 'courier' => 1_200_000, 'tipax' => 0], $tehran); // Tehran price for post

        [$shiraz, $quote] = $this->shippingOptions('فارس', 'شیراز', 'post');
        $this->assertSame(['post' => 900_000, 'tipax' => 0], $shiraz); // no courier outside Tehran
        $this->assertSame(900_000, $quote->shipping);
        $this->assertSame(29_500_000 + 900_000, $quote->total());

        [, $tipax] = $this->shippingOptions('فارس', 'شیراز', 'tipax');
        $this->assertTrue($tipax->shippingReady);
        $this->assertSame(0, $tipax->shipping);

        [, $bad] = $this->shippingOptions('فارس', 'شیراز', 'courier');
        $this->assertFalse($bad->shippingReady);
        $this->assertSame('این روش ارسال برای نشانی انتخاب‌شده ممکن نیست.', $bad->messages['shipping']);
    }

    public function test_free_over_counts_the_discounted_cart_and_coupons_can_free_it(): void
    {
        $this->fillCart(); // 114,000,000 ≥ free_over 50,000,000 → post free
        [$options] = $this->shippingOptions('فارس', 'شیراز');
        $this->assertSame(0, $options['post']);

        app(Cart::class)->clear();
        app(Cart::class)->add($this->variant('3217073')->id, 2); // 59,000,000
        Coupon::create(['code' => 'HALF', 'type' => 'percent', 'amount' => 50]);
        app(Cart::class)->setCoupon('HALF');                       // 29,500,000 after the coupon: below free_over
        [$options] = $this->shippingOptions('فارس', 'شیراز');
        $this->assertSame(900_000, $options['post']);

        Coupon::create(['code' => 'SHIPFREE', 'type' => 'fixed', 'amount' => 0, 'free_shipping' => true]);
        app(Cart::class)->setCoupon('SHIPFREE');
        [$options] = $this->shippingOptions('فارس', 'شیراز');
        $this->assertSame(0, $options['post']);
    }
}
