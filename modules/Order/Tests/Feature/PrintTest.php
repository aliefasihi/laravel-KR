<?php

/**
 * Printing: shipping labels (barcode, COD amount, sender), invoice details from the settings,
 * and the shopper's own invoice from their account.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\Customer\Models\Customer;
use Modules\Order\Filament\Resources\Orders\OrderActions;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Support\Code128;

class PrintTest extends ShopTestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place')->assertHasNoErrors();
        $this->order = Order::query()->sole();
        Settings::set([
            'print.sender_postal_code' => '1166666666', 'print.economic_code' => '411111111111',
            'print.invoice_note' => 'تا ۷ روز امکان مرجوعی هست.', 'print.label_size' => 'a4',
        ]);
    }

    public function test_shipping_label(): void
    {
        $this->get(OrderActions::printUrl([$this->order->id], 'label'))->assertOk()
            ->assertSee('گیرنده')->assertSee('خیابان ولیعصر')->assertSee('1234567890')
            ->assertSee('1166666666')->assertSee('دریافت وجه کالا در محل')
            ->assertSee('<svg', false)->assertSee('size: A4', false);

        $this->get(OrderActions::printUrl([$this->order->id], 'nope'))->assertNotFound();
        $this->get('/orders/print?ids='.$this->order->id.'&kind=label')->assertForbidden();   // unsigned
    }

    public function test_invoice_shows_legal_ids_and_note(): void
    {
        $this->get(OrderActions::printUrl([$this->order->id]))->assertOk()
            ->assertSee('کد اقتصادی: ۴۱۱۱۱۱۱۱۱۱۱۱')->assertSee('تا ۷ روز امکان مرجوعی هست.');
        $this->get(OrderActions::printUrl([$this->order->id], 'slip'))->assertOk()
            ->assertSee('برگه‌ی بسته‌بندی')->assertDontSee('تا ۷ روز امکان مرجوعی هست.');
    }

    public function test_the_customer_prints_only_their_own_invoice(): void
    {
        $this->get(route('customer.orders.show', $this->order))->assertSee(route('customer.orders.invoice', $this->order));
        $this->get(route('customer.orders.invoice', $this->order))->assertOk()->assertSee('فاکتور فروش');

        Settings::set(['print.customer_invoice' => false]);
        $this->get(route('customer.orders.invoice', $this->order))->assertNotFound();
        Settings::set(['print.customer_invoice' => true]);

        Auth::guard('customer')->login(Customer::create(['mobile' => '09129999999']));
        $this->get(route('customer.orders.invoice', $this->order))->assertNotFound();
    }

    public function test_code128_checksum(): void
    {
        // «100245» in code set C: start C, 10, 02, 45, checksum (105 + 10 + 4 + 135) % 103 = 48
        $this->assertSame([105, 10, 2, 45, 48], Code128::values('100245'));
        $this->assertSame([104, 43, 50, 13, 33], array_slice(Code128::values('KR-A'), 0, 5));
    }
}
