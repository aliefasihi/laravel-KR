<?php

/**
 * Coupon rules, the cart field and usage counting.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Tests\Feature;

use Livewire\Livewire;
use Modules\Checkout\Support\Cart;
use Modules\Coupon\Livewire\CouponField;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Support\CouponUsages;
use Modules\Order\Models\Order;
use Modules\Order\Tests\Feature\ShopTestCase;

class CouponTest extends ShopTestCase
{
    private function discount(string $code): array
    {
        $cart = app(Cart::class);
        $cart->setCoupon($code);
        $quote = $cart->quote();

        return [$quote->discount, $quote->messages['coupon'] ?? null, $quote->freeShipping];
    }

    public function test_percent_fixed_caps_and_conditions(): void
    {
        $this->fillCart(); // 84,500,000 + 29,500,000 = 114,000,000 Rials

        Coupon::create(['code' => 'off10', 'type' => 'percent', 'amount' => 10]);
        Coupon::create(['code' => 'CAP', 'type' => 'percent', 'amount' => 50, 'max_discount' => 5_000_000]);
        Coupon::create(['code' => 'FIX', 'type' => 'fixed', 'amount' => 2_000_000, 'free_shipping' => true]);
        Coupon::create(['code' => 'BIG', 'type' => 'percent', 'amount' => 10, 'min_subtotal' => 200_000_000]);
        Coupon::create(['code' => 'OLD', 'type' => 'percent', 'amount' => 10, 'ends_at' => now()->subDay()]);
        Coupon::create(['code' => 'FULL', 'type' => 'percent', 'amount' => 10, 'usage_limit' => 1])->forceFill(['used_count' => 1])->save();

        $this->assertSame([11_400_000, null, false], $this->discount(' Off10 ')); // case/space-insensitive
        $this->assertSame([5_000_000, null, false], $this->discount('cap'));
        $this->assertSame([2_000_000, null, true], $this->discount('FIX'));
        $this->assertSame(0, $this->discount('BIG')[0]);
        $this->assertStringContainsString('۲۰٬۰۰۰٬۰۰۰ تومان', $this->discount('BIG')[1]);
        $this->assertSame('مهلت استفاده از این کد تمام شده است.', $this->discount('OLD')[1]);
        $this->assertSame('ظرفیت استفاده از این کد تمام شده است.', $this->discount('FULL')[1]);
        $this->assertSame('این کد تخفیف معتبر نیست.', $this->discount('NOPE')[1]);
    }

    public function test_product_category_brand_and_sale_rules(): void
    {
        $this->fillCart(); // pan (Goldkish, cookware, on sale) + candy dish (Riton, kitchen, regular price)

        Coupon::create(['code' => 'COOK', 'type' => 'percent', 'amount' => 10, 'category_ids' => [$this->cookware->id]]);
        Coupon::create(['code' => 'KITCHEN', 'type' => 'percent', 'amount' => 10, 'category_ids' => [$this->kitchen->id]]); // incl. subcategories
        Coupon::create(['code' => 'RITON', 'type' => 'percent', 'amount' => 10, 'brand_ids' => [$this->riton->id]]);
        Coupon::create(['code' => 'NOSALE', 'type' => 'percent', 'amount' => 10, 'exclude_sale_items' => true]);
        Coupon::create(['code' => 'DECOR', 'type' => 'percent', 'amount' => 10, 'category_ids' => [$this->decor->id]]);

        $this->assertSame(8_450_000, $this->discount('COOK')[0]);
        $this->assertSame(11_400_000, $this->discount('KITCHEN')[0]);
        $this->assertSame(2_950_000, $this->discount('RITON')[0]);
        $this->assertSame(2_950_000, $this->discount('NOSALE')[0]);
        $this->assertSame('این کد شامل کالاهای سبد شما نمی‌شود.', $this->discount('DECOR')[1]);
    }

    public function test_personal_first_order_and_per_customer_limits(): void
    {
        $this->fillCart();
        Coupon::create(['code' => 'MINE', 'type' => 'percent', 'amount' => 10, 'allowed_mobiles' => ['09350000000']]);
        Coupon::create(['code' => 'ONCE', 'type' => 'percent', 'amount' => 10, 'usage_limit_per_customer' => 1]);
        Coupon::create(['code' => 'FIRST', 'type' => 'percent', 'amount' => 10, 'first_order_only' => true]);

        $this->assertSame('این کد برای حساب شما نیست.', $this->discount('MINE')[1]);
        $this->assertNull($this->discount('ONCE')[1]);
        $this->assertNull($this->discount('FIRST')[1]);

        // an order with ONCE: both limits now apply
        app(CouponUsages::class)->record('ONCE', 1, $this->customer->id, $this->customer->mobile, 100);
        Order::create(['customer_id' => $this->customer->id, 'customer_mobile' => $this->customer->mobile, 'status' => 'completed',
            'recipient_name' => 'x', 'recipient_mobile' => '09121234567', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'x']);
        $this->assertSame('شما قبلاً از این کد استفاده کرده‌اید.', $this->discount('ONCE')[1]);
        $this->assertSame('این کد فقط برای اولین خرید است.', $this->discount('FIRST')[1]);

        // the order is cancelled: the use is given back
        app(CouponUsages::class)->release(1);
        $this->assertNull($this->discount('ONCE')[1]);
        $this->assertSame(0, Coupon::findByCode('ONCE')->used_count);
    }

    public function test_cart_field(): void
    {
        $this->fillCart();
        Coupon::create(['code' => 'YALDA', 'type' => 'fixed', 'amount' => 3_000_000]);

        Livewire::test(CouponField::class)
            ->set('code', 'nope')->call('apply')->assertHasErrors('code')
            ->set('code', 'yalda')->call('apply')->assertHasNoErrors()->assertDispatched('cart-changed');

        $this->get('/cart')->assertOk()
            ->assertSee('کد تخفیف YALDA')
            ->assertSee('۳۰۰٬۰۰۰ تومان', false)
            ->assertSee('۱۱٬۱۰۰٬۰۰۰', false); // 11,400,000 − 300,000 Toman

        Livewire::test(CouponField::class)->call('remove');
        $this->assertNull(app(Cart::class)->coupon());
    }
}
