<?php

/**
 * Admin: orders list/tabs, order page actions, print, sales report.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Tests\Feature;

use App\Models\User;
use Livewire\Livewire;
use Modules\Order\Filament\Pages\SalesReport;
use Modules\Order\Filament\Resources\Orders\OrderActions;
use Modules\Order\Filament\Resources\Orders\Pages\ListOrders;
use Modules\Order\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Order\Filament\Widgets\LatestOrders;
use Modules\Order\Filament\Widgets\OrdersOverview;
use Modules\Order\Filament\Widgets\SalesChart;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Support\SalesReportData;
use Modules\Payment\Support\PaymentReview;

class OrderAdminTest extends ShopTestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $this->order = Order::query()->sole();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_list_view_and_actions(): void
    {
        $this->get('/admin/orders')->assertOk()->assertSee('در حال پردازش');
        $this->get('/admin/orders/'.$this->order->number)->assertOk()->assertSee('خیابان ولیعصر')->assertSee('سفارش ثبت شد');

        Livewire::test(ListOrders::class)->assertCanSeeTableRecords([$this->order])
            ->set('activeTab', 'completed')->assertCanNotSeeTableRecords([$this->order]);

        Livewire::test(ViewOrder::class, ['record' => $this->order->number])
            ->callAction('ship', ['tracking_code' => '۱۲۳۴۵'])
            ->assertHasNoActionErrors();
        $this->order->refresh();
        $this->assertSame('shipped', $this->order->status);
        $this->assertSame('12345', $this->order->tracking_code);

        Livewire::test(ViewOrder::class, ['record' => $this->order->number])
            ->callAction('paymentReceived', ['reference' => 'POS'])
            ->callAction('changeStatus', ['status' => 'completed', 'note' => 'تحویل شد', 'notify' => false]);
        $this->order->refresh();
        $this->assertSame('completed', $this->order->status);
        $this->assertSame('paid', $this->order->payment_status);
        $this->assertStringContainsString('تحویل شد', $this->order->notes()->first()->body);

        $this->get(OrderActions::printUrl([$this->order->id]))->assertOk()->assertSee('فاکتور فروش')->assertSee('خیابان ولیعصر');
        $this->get('/orders/print?ids='.$this->order->id)->assertForbidden(); // unsigned
    }

    public function test_sales_report(): void
    {
        app(PaymentReview::class)->recordManual($this->order, 'cod', null);
        $tz = config('khaneh.display_timezone');
        $kpis = SalesReportData::range(now($tz)->toDateString(), now($tz)->toDateString())->kpis();

        $this->assertSame(1, $kpis['orders']);
        $this->assertSame(114_000_000, $kpis['gross']);
        $this->assertSame(114_000_000, $kpis['revenue']); // post free over 50M
        $this->assertSame(2, $kpis['items']);

        $this->get('/admin/sales-report')->assertOk()->assertSee('درآمد کل')->assertSee('۱۱٬۴۰۰٬۰۰۰', false)
            ->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی');
        Livewire::test(SalesReport::class)->call('applyPreset', 'month')->assertSet('preset', 'month')
            ->call('applyPreset', 'last_month')->assertSet('preset', 'last_month');
        $this->get('/admin')->assertOk(); // dashboard widgets load lazily:
        Livewire::test(OrdersOverview::class)->assertSee('فروش امروز')->assertSee('۱۱٬۴۰۰٬۰۰۰', false);
        Livewire::test(LatestOrders::class)->assertCanSeeTableRecords([$this->order]);
        Livewire::test(SalesChart::class)->assertOk();
    }
}
