<?php

/**
 * Sales channels: a visit from Torob (utm or referer) marks the order «ترب», a return from the bank
 * doesn't erase it, and the sales report shows channels, brands and the selling-tools cards.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Tests\Feature;

use App\Models\User;
use Livewire\Livewire;
use Modules\AbandonedCart\Models\AbandonedCart;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Support\SalesReportData;
use Modules\Order\Support\TrafficSource;

class SalesChannelsTest extends ShopTestCase
{
    public function test_the_order_remembers_where_the_shopper_came_from(): void
    {
        $this->get('/?utm_source=torob')->assertOk();
        $this->assertSame('torob', TrafficSource::current());

        // coming back from the bank page is not a new visit
        $this->get('/', ['referer' => 'https://sep.shaparak.ir/OnlinePG'])->assertOk();
        $this->assertSame('torob', TrafficSource::current());

        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place')->assertHasNoErrors();
        $this->assertSame('torob', Order::query()->sole()->source);
    }

    public function test_referer_hosts_map_to_channels(): void
    {
        $this->get('/', ['referer' => 'https://www.google.com/'])->assertOk();
        $this->assertSame('google', TrafficSource::current());
        $this->get('/', ['referer' => 'https://l.instagram.com/?u=x'])->assertOk();
        $this->assertSame('instagram', TrafficSource::current());
        $this->get('/', ['referer' => 'https://someblog.ir/post'])->assertOk();
        $this->assertSame('other', TrafficSource::current());
        $this->get('/', ['referer' => url('/cart')])->assertOk();   // our own pages don't count
        $this->assertSame('other', TrafficSource::current());
    }

    public function test_report_shows_channels_brands_and_tools(): void
    {
        $this->get('/?utm_source=sms');
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $order = Order::query()->sole();
        AbandonedCart::query()->create([
            'customer_id' => $this->customer->id, 'token' => 'tok', 'items' => [], 'subtotal' => 1, 'active_at' => now(),
            'reminded_at' => now(), 'recovered_at' => now(), 'order_id' => $order->id, 'order_total' => $order->total,
        ]);

        $tz = config('khaneh.display_timezone');
        $data = SalesReportData::range(now($tz)->toDateString(), now($tz)->toDateString());
        $this->assertSame('sms', $data->byChannel()->first()->source);

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/sales-report')->assertOk()
            ->assertSee('کانال‌های فروش')->assertSee('پیامک‌های فروشگاه')
            ->assertSee('برندها')
            ->assertSee('نتیجه‌ی ابزارهای فروش')->assertSee('فروش برگشته با یادآوری');
    }
}
