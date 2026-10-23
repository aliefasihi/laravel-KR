<?php

/**
 * Returns: only delivered orders within the window, the customer's form (items, reason, photo, IBAN),
 * staff approve → receive → refund (stock back, order partially refunded), SMS per step, limits.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Support\OrderWorkflow;
use Modules\Order\Tests\Feature\ShopTestCase;
use Modules\Returns\Filament\Resources\Returns\Pages\ViewReturn;
use Modules\Returns\Livewire\ReturnForm;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\Support\Returns;

class ReturnsTest extends ShopTestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->fillCart(2);   // pan set ×2, candy dish ×1
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $this->order = Order::query()->sole();
        Settings::set(['returns.approved_template' => 'rt-ok', 'returns.refunded_template' => 'rt-money']);
    }

    private function deliver(): void
    {
        $workflow = app(OrderWorkflow::class);
        $workflow->ship($this->order, '123');
        $workflow->changeStatus($this->order->refresh(), 'completed');
        $this->order->refresh();
    }

    public function test_full_flow(): void
    {
        $this->get(route('customer.orders.show', $this->order))->assertDontSee('درخواست مرجوعی');   // not delivered yet
        $this->get(route('customer.returns.create', $this->order))->assertNotFound();

        $this->deliver();
        $this->get(route('customer.orders.show', $this->order))->assertSee('درخواست مرجوعی');
        $pan = $this->order->items->firstWhere('sku', 'GK-7710-STD');
        $stockBefore = $this->variant('GK-7710')->stock;

        Livewire::test(ReturnForm::class, ['order' => $this->order])
            ->set('quantities', [$pan->id => 1])->set('reason', 'damaged')->set('iban', 'IR12 0123 4567 8901 2345 6789 01')
            ->call('submit')->assertHasErrors(['photos']);          // damaged → a photo is needed

        Livewire::test(ReturnForm::class, ['order' => $this->order])
            ->set('quantities', [$pan->id => 1])->set('reason', 'damaged')->set('iban', 'IR120123456789012345678901')
            ->set('photos', [UploadedFile::fake()->image('broken.jpg')])
            ->call('submit')->assertHasNoErrors()->assertRedirect(route('customer.returns'));

        $request = ReturnRequest::query()->sole();
        $this->assertSame(['requested', 'R-'.$this->order->number.'-1', 'IR120123456789012345678901'], [$request->status, $request->number, $request->iban]);
        Storage::disk('local')->assertExists($request->photos[0]);
        $this->get(route('customer.returns'))->assertSee('در انتظار بررسی');

        // the same unit can't be asked twice while open
        $this->assertSame([$pan->id => 1, $this->order->items->firstWhere('sku', '3217073-STD')->id => 1], app(Returns::class)->returnable($this->order->refresh()->load('items')));

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('filament.admin.resources.returns.index'))->assertOk()->assertSee('آسیب‌دیده');
        Livewire::test(ViewReturn::class, ['record' => $request->id])
            ->callAction('approve', ['instructions' => 'با پست بفرستید به شوش، پلاک ۴۷'])
            ->callAction('receive')
            ->callAction('refund', ['amount' => 8_450_000]);   // toman

        $request->refresh();
        $this->assertSame('refunded', $request->status);
        $this->assertSame(84_500_000, $request->refund_amount);
        $this->assertSame($stockBefore + 1, $this->variant('GK-7710')->stock);
        $this->assertSame('partially_refunded', $this->order->refresh()->payment_status);
        $this->assertSame(['rt-ok', 'rt-money'], array_column(array_slice($this->sms, -2), 1));
    }

    public function test_window_closes(): void
    {
        $this->deliver();
        $this->travel(8)->days();
        $this->assertSame([], app(Returns::class)->returnable($this->order->refresh()));
        $this->get(route('customer.returns.create', $this->order))->assertNotFound();
    }
}
