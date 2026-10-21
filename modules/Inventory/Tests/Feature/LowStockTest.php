<?php

/**
 * Low stock: announced once (bell + SMS to the shop), again after a restock, never at night;
 * the admin list shows what to reorder and edits stock in place.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Inventory\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\Inventory\Filament\Resources\LowStock\LowStockResource;
use Modules\Inventory\Filament\Resources\LowStock\Pages\ListLowStock;
use Modules\Inventory\Support\LowStock;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Tests\Feature\ShopTestCase;

class LowStockTest extends ShopTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 12:00', 'Asia/Tehran'));
        $this->admin = User::factory()->create(['is_admin' => true]);
        Settings::set(['inventory.threshold' => 3, 'inventory.sms_template' => 'low-stock', 'inventory.mobiles' => '۰۹۱۲۱۱۱۲۲۳۳, +989350000000']);
    }

    public function test_announced_once_then_again_after_restock(): void
    {
        // stock: GK-7710 6 · GK-2801 0 · 3217073 4 · 3240112 3 → two are at the threshold
        $this->assertSame(2, app(LowStock::class)->scan());
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertStringContainsString('تابه گرانیتی سایز ۲۸', $this->admin->notifications()->first()->data['body']);
        $this->assertCount(2, $this->sms);   // one per shop number
        [$mobile, $template, $values] = $this->sms[0];
        $this->assertSame(['09121112233', 'low-stock', '۰', '۱'], [$mobile, $template, $values[0], $values[1]]);
        $this->assertSame(LowStockResource::getUrl(), $values[2]);

        $this->assertSame(0, app(LowStock::class)->scan());   // nothing new

        // an order takes the candy dish from 4 to 3
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place')->assertHasNoErrors();
        $this->assertSame(2, $this->admin->notifications()->count());   // scanned right after the order
        $this->assertStringContainsString('قندان سنگی', $this->admin->notifications()->get()->pluck('data.body')->implode(' | '));

        // restocked, then sold down again → a new alert
        $this->variant('GK-2801')->update(['stock' => 10]);   // (in the panel a save scans after the response)
        $this->assertSame(0, app(LowStock::class)->scan());
        $this->assertNull($this->variant('GK-2801')->low_stock_notified_at);
        $this->variant('GK-2801')->update(['stock' => 1]);
        $this->assertSame(1, app(LowStock::class)->scan());
        $this->assertSame(3, $this->admin->notifications()->count());
    }

    public function test_no_sms_at_night_the_morning_scan_sends_it(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 23:30', 'Asia/Tehran'));
        $this->assertSame(0, app(LowStock::class)->scan());
        $this->assertSame([], $this->sms);

        $this->travelTo(Carbon::parse('2026-10-21 09:10', 'Asia/Tehran'));
        $this->assertSame(2, app(LowStock::class)->scan());
        $this->assertCount(2, $this->sms);
    }

    public function test_admin_list_and_editing_stock(): void
    {
        $this->actingAs($this->admin);
        $this->get(LowStockResource::getUrl())->assertOk()->assertSee('تابه گرانیتی سایز ۲۸')->assertSee('شمعدان فلزی طلایی')
            ->assertDontSee('سرویس قابلمه ۱۰ پارچه گرانیتی')->assertSee('تمام شده');

        $variant = $this->variant('GK-2801');
        Livewire::test(ListLowStock::class)->assertCanSeeTableRecords([$variant])
            ->call('updateTableColumnState', 'stock', (string) $variant->id, '12');
        $this->assertSame(12, (int) $variant->fresh()->stock);
        Livewire::test(ListLowStock::class)->assertCanNotSeeTableRecords([$variant])
            ->set('activeTab', 'all')->assertCanSeeTableRecords([$variant]);

        $this->assertSame('~۱۵ روز', LowStockResource::daysLeft(10, 20));
        $this->assertSame('فروش نداشته', LowStockResource::daysLeft(3, 0));
    }
}
