<?php

/**
 * Checkout → order: totals, stock, coupons, payment methods, the shopper's order pages.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Checkout\Support\Cart;
use Modules\Coupon\Models\Coupon;
use Modules\Customer\Models\Customer;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Support\OrderWorkflow;
use Modules\Payment\Livewire\ReceiptUpload;
use Modules\Payment\Models\Payment;
use Modules\Payment\Support\PaymentReview;

class CheckoutTest extends ShopTestCase
{
    public function test_cash_on_delivery_order_with_coupon_and_shipping(): void
    {
        $this->fillCart(2); // 2 × 84,500,000 + 29,500,000 = 198,500,000
        Coupon::create(['code' => 'YALDA', 'type' => 'fixed', 'amount' => 5_000_000]);
        app(Cart::class)->setCoupon('YALDA');

        $this->get('/checkout')->assertOk()->assertSee('ثبت سفارش و پرداخت')->assertSee('پرداخت در محل');

        Livewire::test(Checkout::class)
            ->assertSet('addressId', $this->tehran->id)
            ->set('shippingMethod', 'courier')
            ->set('paymentMethod', 'cod')
            ->set('note', 'زنگ خراب است')
            ->call('place')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = Order::query()->with('items')->sole();
        $this->assertSame('processing', $order->status);           // COD starts «در حال پردازش»
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(198_500_000, $order->subtotal);
        $this->assertSame(5_000_000, $order->discount);
        $this->assertSame(1_200_000, $order->shipping_total);      // courier, not free_over (couriers have none)
        $this->assertSame(198_500_000 - 5_000_000 + 1_200_000, $order->total);
        $this->assertSame('YALDA', $order->coupon_code);
        $this->assertSame((string) (100000 + $order->id), $order->number);
        $this->assertSame('خیابان ولیعصر', $order->address);
        $this->assertSame(2, $order->items->firstWhere('sku', 'GK-7710-STD')->quantity);

        // stock taken, cart emptied, coupon counted
        $this->assertSame(4, $this->variant('GK-7710')->stock);
        $this->assertSame(3, $this->variant('3217073')->stock);
        $this->assertSame(0, app(Cart::class)->count());
        $this->assertSame(1, Coupon::findByCode('YALDA')->used_count);

        // shopper's pages
        $this->get('/account/orders')->assertOk()->assertSee($order->displayNumber());
        $this->get('/account/orders/'.$order->number.'?placed=1')->assertOk()
            ->assertSee('سفارش شما ثبت شد')->assertSee('پرداخت در محل')->assertSee('زنگ خراب است');

        // cancelling (by staff here) gives stock and the coupon back
        app(OrderWorkflow::class)->changeStatus($order, 'cancelled');
        $this->assertSame(6, $this->variant('GK-7710')->stock);
        $this->assertSame(0, Coupon::findByCode('YALDA')->used_count);
    }

    public function test_cod_only_in_tehran_and_stock_and_price_checks(): void
    {
        $this->fillCart();

        $component = Livewire::test(Checkout::class)->set('addressId', $this->shiraz->id)->set('paymentMethod', 'card');
        $this->assertArrayNotHasKey('cod', $component->viewData('payments'));     // COD: Tehran only
        $this->assertArrayHasKey('zarinpal', $component->viewData('payments'));

        // someone else bought the last candy dishes meanwhile
        $this->variant('3217073')->update(['stock' => 0]);
        $component->call('place')->assertHasErrors('total'); // the total on screen is gone
        $this->assertSame(0, Order::count());
        $component->call('place')->assertHasNoErrors();                                   // seen the new total: fine
        $this->assertSame(1, Order::query()->sole()->items()->count());
    }

    public function test_card_to_card_receipt_and_staff_approval(): void
    {
        Storage::fake('local');
        $this->fillCart();

        Livewire::test(Checkout::class)->set('addressId', $this->shiraz->id)->set('shippingMethod', 'post')->set('paymentMethod', 'card')->call('place');
        $order = Order::query()->sole();
        $this->assertSame('on_hold', $order->status);

        $this->get('/account/orders/'.$order->number)->assertOk()->assertSee('۶۰۳۷')->assertSee('ثبت رسید پرداخت');

        Livewire::test(ReceiptUpload::class, ['orderId' => $order->id])
            ->set('receipt', UploadedFile::fake()->image('receipt.jpg'))
            ->set('reference', '۱۲۳۴۵۶')
            ->call('submit')->assertHasNoErrors();

        $payment = Payment::query()->sole();
        $this->assertSame('review', $payment->status);
        $this->assertSame('123456', $payment->reference);
        Storage::disk('local')->assertExists($payment->receipt_path);
        $this->assertSame('pending_review', $order->fresh()->payment_status);

        // the receipt is only reachable with a signed link
        $this->get('/payment/receipts/'.$payment->id)->assertForbidden();

        app(PaymentReview::class)->approve($payment);
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
    }

    public function test_zarinpal_pay_verify_and_cancel(): void
    {
        Http::fake([
            'payment.zarinpal.com/pg/v4/payment/request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'A000000000000000000000000000000AUTH'], 'errors' => []]),
            'payment.zarinpal.com/pg/v4/payment/verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 201, 'card_pan' => '502229******5995'], 'errors' => []]),
        ]);
        $this->fillCart();

        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'zarinpal')->call('place')
            ->assertRedirect('https://payment.zarinpal.com/pg/StartPay/A000000000000000000000000000000AUTH');

        $order = Order::query()->sole();
        $this->assertSame('pending', $order->status);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'request.json') && $r['amount'] === $order->total && $r['currency'] === 'IRR'
            && $r['callback_url'] === route('payment.zarinpal.callback'));

        // back from the bank: OK → verified → paid
        $this->get('/payment/zarinpal/callback?Authority=A000000000000000000000000000000AUTH&Status=OK')
            ->assertRedirect(route('customer.orders.show', ['order' => $order, 'placed' => 1]));
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame('201', Payment::query()->sole()->reference);

        // a reload of the callback doesn't verify twice
        $this->get('/payment/zarinpal/callback?Authority=A000000000000000000000000000000AUTH&Status=OK')->assertRedirect();
        Http::assertSentCount(2);
    }

    public function test_unpaid_online_orders_give_stock_back(): void
    {
        Http::fake(['payment.zarinpal.com/*' => Http::response(['data' => ['code' => 100, 'authority' => 'A1'], 'errors' => []])]);
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'zarinpal')->call('place');
        $this->assertSame(5, $this->variant('GK-7710')->stock);

        // bank page closed: Status=NOK
        $this->get('/payment/zarinpal/callback?Authority=A1&Status=NOK')->assertRedirect();
        $this->assertSame('cancelled', Payment::query()->sole()->status);

        $this->travel(31)->minutes();
        $this->artisan('orders:cancel-unpaid')->assertSuccessful();
        $this->assertSame('cancelled', Order::query()->sole()->status);
        $this->assertSame(6, $this->variant('GK-7710')->stock);
    }

    public function test_refunds_and_access_to_other_peoples_orders(): void
    {
        $this->fillCart(2);
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $order = Order::query()->with('items')->sole();
        app(PaymentReview::class)->recordManual($order, 'cod', 'POS-1');
        $pan = $order->items->firstWhere('sku', 'GK-7710-STD');

        app(OrderWorkflow::class)->refund($order->fresh('items'), 84_500_000, [$pan->id => 1], 'یکی شکسته بود');
        $order->refresh();
        $this->assertSame('partially_refunded', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(5, $this->variant('GK-7710')->stock); // 6 − 2 + 1

        app(OrderWorkflow::class)->refund($order->fresh('items'), $order->total);
        $this->assertSame('refunded', $order->fresh()->status);

        // another customer can't see it
        $other = Customer::create(['mobile' => '09350000000']);
        Auth::guard('customer')->login($other);
        $this->get('/account/orders/'.$order->number)->assertNotFound();
    }

    public function test_sms_on_payment_and_shipping(): void
    {
        Settings::set(['order_sms.paid_template' => 'paid', 'order_sms.shipped_template' => 'sent', 'order_sms.placed_template' => 'placed']);
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $order = Order::query()->sole();

        app(OrderWorkflow::class)->ship($order->load('items'), 'TRK-99');
        app()->terminate(); // SMS go out after the response

        $sent = collect($this->sms)->map(fn ($s) => [$s[0], $s[1], $s[2]['ORDER'], $s[2]['TRACKING']])->all();
        $this->assertContains(['09121234567', 'placed', $order->number, '-'], $sent);
        $this->assertContains(['09121234567', 'sent', $order->number, 'TRK-99'], $sent);
    }
}
