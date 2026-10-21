<?php

/**
 * Wishlist + «tell me when it's back»: saving needs an account, guests can leave a mobile number,
 * one SMS when stock returns, one per real price drop, nothing at night.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wishlist\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Customer\Models\Customer;
use Modules\Sms\Support\Sms;
use Modules\Sms\Support\SmsDriver;
use Modules\Wishlist\Livewire\ProductActions;
use Modules\Wishlist\Models\StockAlert;
use Modules\Wishlist\Models\WishlistItem;
use Modules\Wishlist\Support\Watchers;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    /** @var list<array{0: string, 1: string, 2: array}> */
    public array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $brand = Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']);
        $this->product = Product::create(['brand_id' => $brand->id, 'sku' => 'R-1', 'name' => 'قندان سنگی', 'status' => Product::STATUS_ACTIVE]);
        $this->product->variants()->create(['sku' => 'R-1-1', 'price' => 30_000_000, 'stock' => 0]);

        $test = $this;
        app(Sms::class)->use(new class($test) implements SmsDriver
        {
            public function __construct(private WishlistTest $test) {}

            public function sendOtp(string $mobile, string $code): void {}

            public function sendTemplate(string $mobile, string $template, array $values): void
            {
                $this->test->sent[] = [$mobile, $template, $values];
            }

            public function sendText(string $mobile, string $text): void {}
        });
        Settings::set(['wishlist.back_in_stock_template' => 'instock', 'wishlist.price_drop_template' => 'cheaper']);
        Carbon::setTestNow(Carbon::parse('2026-10-21 12:00', 'Asia/Tehran'));
    }

    public function test_guests_leave_a_mobile_and_get_one_sms_when_it_is_back(): void
    {
        Livewire::test(ProductActions::class, ['product' => $this->product])
            ->set('mobile', '۰۹۱۲ ۱۲۳ ۴۵۶۷')->call('notify')->assertSet('requested', true);
        Livewire::test(ProductActions::class, ['product' => $this->product])
            ->set('mobile', '123')->call('notify')->assertSet('error', 'شماره موبایل درست نیست.');
        $this->assertSame(1, StockAlert::query()->count());

        app(Watchers::class)->check($this->product->id);
        $this->assertSame([], $this->sent);                                   // still out of stock

        $this->product->variants()->first()->update(['stock' => 3]);
        app(Watchers::class)->check($this->product->id);
        app(Watchers::class)->check($this->product->id);
        $this->assertCount(1, $this->sent);                                   // once
        $this->assertSame(['09121234567', 'instock'], [$this->sent[0][0], $this->sent[0][1]]);
        $this->assertSame('قندان سنگی', $this->sent[0][2][3]);
        $this->assertNotNull(StockAlert::query()->first()->notified_at);
    }

    public function test_saving_needs_an_account_and_a_real_price_drop_sends_one_sms(): void
    {
        Livewire::test(ProductActions::class, ['product' => $this->product])->call('toggle')->assertRedirect();   // guest → login

        $customer = Customer::create(['mobile' => '09125556677']);
        Auth::guard('customer')->login($customer);
        $this->product->variants()->first()->update(['stock' => 5]);
        Livewire::test(ProductActions::class, ['product' => $this->product])->call('toggle')->assertSet('saved', true);
        $this->assertSame(30_000_000, WishlistItem::query()->first()->watched_price);

        $variant = $this->product->variants()->first();
        $variant->update(['sale_price' => 29_900_000]);                       // < 1%: not worth an SMS
        app(Watchers::class)->check($this->product->id);
        $this->assertSame([], $this->sent);

        $variant->update(['sale_price' => 25_000_000]);
        app(Watchers::class)->check($this->product->id);
        app(Watchers::class)->check($this->product->id);
        $this->assertCount(1, $this->sent);
        $this->assertSame(['09125556677', 'cheaper'], [$this->sent[0][0], $this->sent[0][1]]);
        $this->assertSame(25_000_000, WishlistItem::query()->first()->watched_price);

        $this->get(route('customer.wishlist'))->assertOk()->assertSee('قندان سنگی');
    }

    public function test_nothing_is_sent_at_night_and_the_sweep_sends_it_in_the_morning(): void
    {
        StockAlert::query()->create(['product_id' => $this->product->id, 'mobile' => '09121234567']);
        $this->product->variants()->first()->update(['stock' => 1]);

        Carbon::setTestNow(Carbon::parse('2026-10-21 23:30', 'Asia/Tehran'));
        app(Watchers::class)->sweep();
        $this->assertSame([], $this->sent);

        Carbon::setTestNow(Carbon::parse('2026-10-22 09:05', 'Asia/Tehran'));
        app(Watchers::class)->sweep();
        $this->assertCount(1, $this->sent);
    }

    public function test_the_buttons_are_on_every_product_page_design(): void
    {
        foreach (['classic', 'noir', 'pop', 'arch', 'bazaar'] as $design) {
            Settings::set(['design.product' => $design]);
            $this->get($this->product->url())->assertOk()->assertSee('موجود شد خبرم کن')->assertSee('افزودن به علاقه‌مندی‌ها');
        }
    }
}
