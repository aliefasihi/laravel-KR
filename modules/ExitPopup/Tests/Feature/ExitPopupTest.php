<?php

/**
 * Exit popup: which pages and visitors carry it, the shared code goes on the cart, personal codes
 * (one per mobile, SMS, newsletter), the cart reminder, counts, the settings page.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\ExitPopup\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\Checkout\Support\Cart;
use Modules\Content\Models\NewsletterSubscriber;
use Modules\Coupon\Models\Coupon;
use Modules\ExitPopup\Support\ExitPopup;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Tests\Feature\ShopTestCase;

class ExitPopupTest extends ShopTestCase
{
    private Coupon $coupon;

    protected function setUp(): void
    {
        parent::setUp();
        Auth::guard('customer')->logout();
        $this->coupon = Coupon::create(['code' => 'BYE10', 'type' => 'percent', 'amount' => 10]);
        Settings::set(['exit_popup.enabled' => true, 'exit_popup.offer' => 'coupon', 'exit_popup.coupon_id' => $this->coupon->id]);
    }

    public function test_pages_and_visitors(): void
    {
        $this->get('/')->assertSee('exit-popup-config', false)->assertSee('BYE10')->assertSee('استفاده از کد');
        $this->get(route('checkout.cart'))->assertDontSee('exit-popup-config', false);   // never while buying

        Settings::set(['exit_popup.pages' => ['product']]);
        $this->get('/')->assertDontSee('exit-popup-config', false);
        $this->get($this->variant('3217073')->product->url())->assertSee('exit-popup-config', false);
        Settings::set(['exit_popup.pages' => array_keys(ExitPopup::PAGES)]);

        $this->coupon->update(['ends_at' => now()->subDay()]);   // nothing to offer
        $this->get('/')->assertDontSee('exit-popup-config', false);
        $this->coupon->update(['ends_at' => null]);

        // customers who already ordered (default audience) and staff don't see it
        Auth::guard('customer')->login($this->customer);
        $this->get('/')->assertSee('exit-popup-config', false);
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $this->get('/')->assertDontSee('exit-popup-config', false);
        Auth::guard('customer')->logout();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/')->assertDontSee('exit-popup-config', false);

        Settings::set(['exit_popup.enabled' => false]);
        Auth::guard('web')->logout();
        $this->get('/')->assertDontSee('exit-popup-config', false);
    }

    public function test_shared_code_and_cart_reminder(): void
    {
        $this->postJson(route('exit-popup.event'), ['type' => 'shown'])->assertOk();
        $this->postJson(route('exit-popup.apply'))->assertOk()->assertJson(['redirect' => route('catalog.shop')]);
        $this->assertSame('BYE10', app(Cart::class)->coupon());
        $this->assertSame(['shown' => 1, 'closed' => 0, 'converted' => 1, 'leads' => 0], ExitPopup::stats());

        $this->fillCart();
        $this->get('/')->assertSee('سبد خریدتان هنوز منتظر است!')->assertSee('۲ کالا به ارزش ۱۱٬۴۰۰٬۰۰۰ تومان', false)
            ->assertSee('اعمال کد و تکمیل خرید');
        $this->postJson(route('exit-popup.apply'))->assertJson(['redirect' => route('checkout.cart')]);

        Settings::set(['exit_popup.offer' => 'message']);
        $this->postJson(route('exit-popup.apply'))->assertNotFound();
    }

    public function test_personal_codes(): void
    {
        $this->coupon->update(['is_active' => false, 'min_subtotal' => 50_000_000]);   // template only
        Settings::set(['exit_popup.offer' => 'personal', 'exit_popup.personal_days' => 2, 'exit_popup.sms_template' => 'exit-code']);
        $this->get('/')->assertSee('کد تخفیف من')->assertDontSee('BYE10');

        $this->postJson(route('exit-popup.lead'), ['mobile' => '0912'])->assertStatus(422);
        $code = $this->postJson(route('exit-popup.lead'), ['mobile' => '۰۹۳۵۱۲۳۴۵۶۷'])->assertOk()->json('code');

        $personal = Coupon::findByCode($code);
        $this->assertStringStartsWith('EX', $code);
        $this->assertSame([['09351234567'], 1, true, 50_000_000], [$personal->allowed_mobiles, $personal->usage_limit, $personal->is_active, $personal->min_subtotal]);
        $this->assertTrue($personal->ends_at->isSameDay(now()->addDays(2)));
        $this->assertSame(['09351234567', 'exit-code', [$code, '۱۰٪', '2']], end($this->sms));
        $this->assertSame($code, app(Cart::class)->coupon());
        $this->assertTrue(NewsletterSubscriber::query()->where('contact', '09351234567')->where('source', 'exit-popup')->exists());

        // the same number again: the same code, no new coupon, no new SMS
        $smsCount = count($this->sms);
        $this->postJson(route('exit-popup.lead'), ['mobile' => '09351234567'])->assertJson(['code' => $code]);
        $this->assertSame(2, Coupon::query()->count());
        $this->assertCount($smsCount, $this->sms);
        $this->assertSame(1, ExitPopup::stats()['leads']);
    }

    public function test_settings_page(): void
    {
        ExitPopup::hit('shown');
        ExitPopup::hit('shown');
        ExitPopup::hit('converted');
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/settings')->assertOk()->assertSee('پاپ‌آپ خروج')->assertSee('۲ بار نمایش', false);
    }
}
