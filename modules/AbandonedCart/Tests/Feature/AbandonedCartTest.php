<?php

/**
 * Abandoned-cart reminder: copied for signed-in shoppers, one SMS after the delay (not at night),
 * the link refills the cart, an order afterwards counts as recovered.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\AbandonedCart\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Modules\AbandonedCart\Models\AbandonedCart;
use Modules\AbandonedCart\Support\Reminders;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Checkout\Support\Cart;
use Modules\Customer\Models\Customer;
use Modules\Sms\Support\Sms;
use Modules\Sms\Support\SmsDriver;
use Tests\TestCase;

class AbandonedCartTest extends TestCase
{
    use RefreshDatabase;

    public array $sent = [];

    private int $variantId;

    protected function setUp(): void
    {
        parent::setUp();
        $brand = Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']);
        $product = Product::create(['brand_id' => $brand->id, 'sku' => 'R-1', 'name' => 'قندان', 'status' => Product::STATUS_ACTIVE]);
        $this->variantId = $product->variants()->create(['sku' => 'R-1-1', 'price' => 30_000_000, 'stock' => 5])->id;

        $test = $this;
        app(Sms::class)->use(new class($test) implements SmsDriver
        {
            public function __construct(private AbandonedCartTest $test) {}

            public function sendOtp(string $mobile, string $code): void {}

            public function sendTemplate(string $mobile, string $template, array $values): void
            {
                $this->test->sent[] = [$mobile, $template, $values];
            }

            public function sendText(string $mobile, string $text): void {}
        });
        Settings::set(['abandoned.template' => 'cart-reminder', 'abandoned.coupon_code' => 'BACK10']);
        Carbon::setTestNow(Carbon::parse('2026-10-21 11:00', 'Asia/Tehran'));
    }

    private function order(int $customerId, int $total): array
    {
        return ['id' => 7, 'number' => 'KR-1', 'status' => 'pending', 'payment_status' => 'unpaid', 'customer_id' => $customerId,
            'mobile' => null, 'name' => null, 'total' => $total, 'discount' => 0, 'coupon_code' => null, 'tracking_code' => null, 'notify' => false];
    }

    public function test_one_reminder_after_the_delay_and_the_link_refills_the_cart(): void
    {
        app(Cart::class)->add($this->variantId);                         // a guest: nothing kept
        $this->assertSame(0, AbandonedCart::query()->count());

        $customer = Customer::create(['mobile' => '09121234567', 'first_name' => 'سارا']);
        Auth::guard('customer')->login($customer);                       // signing in with a full cart keeps a copy
        $saved = AbandonedCart::query()->firstOrFail();
        $this->assertSame([(string) $this->variantId => 1], array_map('intval', $saved->items));
        $this->assertSame(30_000_000, $saved->subtotal);

        $this->assertSame(0, app(Reminders::class)->sendDue());          // too soon
        Carbon::setTestNow(now()->addHours(3));
        $this->assertSame(1, app(Reminders::class)->sendDue());
        $this->assertSame(0, app(Reminders::class)->sendDue());          // once
        [$mobile, $template, $values] = $this->sent[0];
        $this->assertSame(['09121234567', 'cart-reminder', 'BACK10', 'سارا'], [$mobile, $template, $values[1], $values[3]]);

        // the link, on another device
        Auth::guard('customer')->logout();
        $this->flushSession();
        $this->get($values[0])->assertRedirect(route('checkout.cart'));
        $this->assertSame(1, app(Cart::class)->quantityOf($this->variantId));
        $this->assertSame('BACK10', app(Cart::class)->coupon());

        Event::dispatch('order.placed', [$this->order($customer->id, 27_000_000)]);
        $this->assertSame('recovered', $saved->fresh()->status());
        $this->assertSame(27_000_000, $saved->fresh()->order_total);
    }

    public function test_no_sms_at_night_and_an_order_without_reminder_clears_it(): void
    {
        $customer = Customer::create(['mobile' => '09121234567']);
        Auth::guard('customer')->login($customer);
        app(Cart::class)->add($this->variantId);

        Carbon::setTestNow(Carbon::parse('2026-10-21 23:00', 'Asia/Tehran'));
        $this->assertSame(0, app(Reminders::class)->sendDue());

        Event::dispatch('order.placed', [$this->order($customer->id, 1)]);
        $this->assertSame(0, AbandonedCart::query()->count());
    }
}
