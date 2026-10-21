<?php

/**
 * SnappPay instalments: offered only for eligible amounts, token with the order's lines, the
 * cross-site callback verifies + settles once, a refusal leaves the order unpaid.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Tests\Feature\ShopTestCase;
use Modules\Payment\Models\Payment;

class SnappPayTest extends ShopTestCase
{
    private const BASE = 'https://fms-gateway-staging.snapp.test';

    protected function setUp(): void
    {
        parent::setUp();
        Settings::set([
            'payment.snapppay_enabled' => true, 'payment.snapppay_base_url' => self::BASE,
            'payment.snapppay_client_id' => 'cid', 'payment.snapppay_client_secret' => 'secret',
            'payment.snapppay_username' => 'user', 'payment.snapppay_password' => 'pass',
        ]);
    }

    private function fake(bool $eligible = true): void
    {
        Http::fake([
            self::BASE.'/api/online/v1/oauth/token' => Http::response(['access_token' => 'jwt', 'token_type' => 'bearer']),
            self::BASE.'/api/online/offer/v1/eligible*' => Http::response(['successful' => true, 'response' => ['eligible' => $eligible, 'title_message' => 'اسنپ‌پی']]),
            self::BASE.'/api/online/payment/v1/token' => Http::response(['successful' => true, 'response' => ['paymentToken' => 'PT-1', 'paymentPageUrl' => 'https://pay.snapp.test/PT-1']]),
            self::BASE.'/api/online/payment/v1/verify' => Http::response(['successful' => true, 'response' => ['transactionId' => 'x']]),
            self::BASE.'/api/online/payment/v1/settle' => Http::response(['successful' => true, 'response' => ['transactionId' => 'x']]),
        ]);
    }

    public function test_instalments_from_checkout_to_paid(): void
    {
        $this->fake();
        $this->fillCart();

        $this->get($this->variant('GK-7710')->product->url())->assertSee('اسنپ‌پی');   // «۴ قسط» under the price

        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'snapppay')->call('place')
            ->assertRedirect('https://pay.snapp.test/PT-1');
        $order = Order::query()->sole();
        $payment = Payment::query()->sole();
        $this->assertSame('pending', $order->status);
        $this->assertMatchesRegularExpression('/^\d{10}$/', $payment->authority);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/payment/v1/token')
            && $r['mobile'] === '+989121234567' && $r['amount'] === $order->total + $order->discount
            && $r['returnURL'] === route('payment.snapppay.callback') && count($r['cartList'][0]['cartItems']) === 2);

        // SnappPay posts back from the browser (no CSRF token)
        $this->post('/payment/snapppay/callback', ['transactionId' => $payment->authority, 'state' => 'OK', 'amount' => $order->total])
            ->assertRedirectContains('/payment/snapppay/done/');
        $order->refresh();
        $this->assertSame(['paid', 'processing'], [$order->payment_status, $order->status]);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/settle') && $r['paymentToken'] === 'PT-1');

        // twice: no second verify
        $this->post('/payment/snapppay/callback', ['transactionId' => $payment->authority, 'state' => 'OK', 'amount' => $order->total])->assertRedirect();
        $this->assertSame(1, collect(Http::recorded())->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/verify'))->count());
    }

    public function test_not_offered_when_snapppay_says_no_or_out_of_range(): void
    {
        $this->fake(eligible: false);
        $this->fillCart();
        $this->assertArrayNotHasKey('snapppay', Livewire::test(Checkout::class)->set('shippingMethod', 'post')->viewData('payments'));
    }

    public function test_refused_credit_leaves_the_order_unpaid(): void
    {
        $this->fake();
        $this->fillCart();
        Settings::set(['payment.snapppay_max' => 10_000_000]);                // 1,000,000 toman: this cart is above it
        $this->assertArrayNotHasKey('snapppay', Livewire::test(Checkout::class)->set('shippingMethod', 'post')->viewData('payments'));

        Settings::set(['payment.snapppay_max' => 0]);
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'snapppay')->call('place');
        $payment = Payment::query()->sole();
        $this->post('/payment/snapppay/callback', ['transactionId' => $payment->authority, 'state' => 'FAILED', 'amount' => 1])->assertRedirect();
        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertSame('unpaid', Order::query()->sole()->payment_status);
    }
}
