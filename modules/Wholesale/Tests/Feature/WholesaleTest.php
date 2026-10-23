<?php

/**
 * Wholesale: price tiers and rules (product → closest category → brand → default), the cart and
 * the order use the wholesale unit price only for approved customers above the minimum, coupons
 * don't stack; the application → approve/reject flow, the price list's quick order, admin pages.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\Checkout\Support\Cart;
use Modules\Coupon\Models\Coupon;
use Modules\Crm\Models\FormSubmission;
use Modules\Customer\Models\Customer;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Tests\Feature\ShopTestCase;
use Modules\Wholesale\Filament\Resources\Accounts\Pages\ListWholesaleAccounts;
use Modules\Wholesale\Filament\Resources\Accounts\Pages\ViewWholesaleAccount;
use Modules\Wholesale\Filament\Resources\Rules\Pages\ManageWholesaleRules;
use Modules\Wholesale\Livewire\ApplicationForm;
use Modules\Wholesale\Livewire\InquiryForm;
use Modules\Wholesale\Livewire\PriceList;
use Modules\Wholesale\Models\WholesaleAccount;
use Modules\Wholesale\Models\WholesaleRule;
use Modules\Wholesale\Support\Wholesale;

class WholesaleTest extends ShopTestCase
{
    private function approve(Customer $customer): WholesaleAccount
    {
        $account = WholesaleAccount::query()->create([
            'customer_id' => $customer->id, 'status' => 'approved', 'approved_at' => now(),
            'shop_name' => 'گالری نو', 'owner_name' => 'علی', 'business_type' => 'shop', 'city' => 'تهران',
        ]);
        $this->fresh();

        return $account;
    }

    /** a new request's worth of state (the cart and wholesale lookups are kept per request) */
    private function fresh(): void
    {
        $this->app->forgetScopedInstances();
    }

    private function prices(): array
    {
        $this->fresh();

        return app(Cart::class)->lines()->mapWithKeys(fn ($l) => [$l->variant->sku => [$l->unitPrice(), $l->priceLabel]])->all();
    }

    public function test_tiers_and_rules(): void
    {
        $wholesale = app(Wholesale::class);
        $dish = $this->variant('3217073');   // 29,500,000, no sale
        $pan = $this->variant('GK-7710');    // 99,000,000, on sale 84,500,000

        // defaults 1+ → 10%, 6+ → 13%, 12+ → 15%; rounded to 1,000 toman
        $this->assertSame(26_550_000, $wholesale->priceFor($dish));
        $this->assertSame(25_670_000, $wholesale->priceFor($dish, 6));
        $this->assertNull($wholesale->priceFor($pan, 1));                   // the sale price is already lower
        $this->assertSame(84_150_000, $wholesale->priceFor($pan, 12));
        $this->assertSame([1, 6, 12], array_column($wholesale->ladder($dish), 'min_qty'));

        // category «آشپزخانه» 20% (incl. its sub-category), brand ریتون 25%, the pan itself: none
        WholesaleRule::query()->create(['scope' => 'category', 'target_id' => $this->kitchen->id, 'tiers' => [['min_qty' => 1, 'percent' => 20]]]);
        WholesaleRule::query()->create(['scope' => 'brand', 'target_id' => $this->riton->id, 'tiers' => [['min_qty' => 1, 'percent' => 25]]]);
        $this->fresh();
        $wholesale = app(Wholesale::class);
        $this->assertSame(23_600_000, $wholesale->priceFor($dish));          // category beats brand
        $this->assertSame(79_200_000, $wholesale->priceFor($pan));           // via the parent category
        $this->assertSame(20_550_000, $wholesale->priceFor($this->variant('3240112')));   // decor: brand rule

        WholesaleRule::query()->create(['scope' => 'product', 'target_id' => $pan->product_id, 'tiers' => []]);
        $this->fresh();
        $this->assertNull(app(Wholesale::class)->priceFor($pan, 12));
    }

    public function test_cart_and_order_use_wholesale_prices(): void
    {
        $this->fillCart();   // pan ×1 (sale 84,500,000), dish ×1
        $this->assertSame(['GK-7710-STD' => [84_500_000, null], '3217073-STD' => [29_500_000, null]], $this->prices());

        $this->approve($this->customer);
        $this->assertSame(['GK-7710-STD' => [84_500_000, null], '3217073-STD' => [26_550_000, Wholesale::LABEL]], $this->prices());

        // below the minimum (cart at retail = 11,400,000 toman) → retail prices, and how much is left
        Settings::set(['wholesale.min_order' => 12_000_000]);
        $this->assertNull($this->prices()['3217073-STD'][1]);
        $this->get(route('checkout.cart'))->assertSee('۶۰۰٬۰۰۰ تومان دیگر خرید کنید', false);
        Settings::set(['wholesale.min_order' => 10_000_000]);
        $this->get(route('checkout.cart'))->assertSee('قیمت همکار اعمال شد');

        // coupons don't stack
        Coupon::create(['code' => 'OFF10', 'type' => 'percent', 'amount' => 10]);
        $this->fresh();
        app(Cart::class)->setCoupon('OFF10');
        $quote = app(Cart::class)->quote();
        $this->assertSame(0, $quote->discount);
        $this->assertStringContainsString('قیمت همکار', $quote->messages['coupon']);
        app(Cart::class)->setCoupon(null);

        $this->fresh();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $order = Order::query()->sole();
        $this->assertSame(26_550_000, $order->items->firstWhere('sku', '3217073-STD')->unit_price);
        $this->assertSame(84_500_000 + 26_550_000, $order->subtotal);
        $this->assertTrue($order->notes()->where('body', 'like', '%سفارش همکار%')->exists());
    }

    public function test_application_flow(): void
    {
        Settings::set(['wholesale.approved_template' => 'ws-ok', 'wholesale.rejected_template' => 'ws-no']);

        Auth::guard('customer')->logout();
        $this->get(route('wholesale'))->assertOk()->assertSee('ورود و ثبت درخواست');
        $this->get($this->variant('3217073')->product->url())->assertSee('مغازه‌دار یا همکار هستید؟');

        Auth::guard('customer')->login($this->customer);
        Livewire::test(ApplicationForm::class)
            ->set('shop_name', 'گالری نو')->set('business_type', 'shop')->set('province', 'تهران')->set('city', 'تهران')
            ->set('phone', '۰۲۱۸۸۸۸۸۸۸۸')->set('website', '@galerino')
            ->call('submit')->assertHasNoErrors()->assertRedirect(route('wholesale'));

        $account = WholesaleAccount::query()->sole();
        $this->assertSame(['pending', 'علی فصیحی', '02188888888'], [$account->status, $account->owner_name, $account->phone]);
        $this->get(route('wholesale'))->assertSee('در حال بررسی');

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('filament.admin.resources.wholesale-accounts.index'))->assertOk()->assertSee('گالری نو');
        Livewire::test(ViewWholesaleAccount::class, ['record' => $account->id])->callAction('reject', ['reason' => 'پیج فعال لازم است']);
        $this->assertSame(['09121234567', 'ws-no', ['گالری نو', 'پیج فعال لازم است']], end($this->sms));
        $this->get(route('wholesale'))->assertSee('پیج فعال لازم است')->assertSee('ثبت درخواست همکاری');

        Livewire::test(ViewWholesaleAccount::class, ['record' => $account->id])->callAction('approve');
        $this->assertSame('approved', $account->refresh()->status);
        $this->assertSame('ws-ok', end($this->sms)[1]);

        // approved: the price list, the product page ladder, quick order
        $this->fresh();
        $this->get(route('wholesale'))->assertSee('لیست قیمت همکار')->assertSee('۲٬۶۵۵٬۰۰۰');
        $this->get($this->variant('3217073')->product->url())->assertSee('قیمت همکار')->assertDontSee('مغازه‌دار یا همکار هستید؟');
        $dish = $this->variant('3217073');
        Livewire::test(PriceList::class)->set('qty', [$dish->id => '۲'])->call('addAll')->assertDispatched('cart-updated');
        $this->assertSame(2, app(Cart::class)->quantityOf($dish->id));

        // not for others
        Auth::guard('customer')->login(Customer::create(['mobile' => '09350000000']));
        $this->fresh();
        Livewire::test(PriceList::class)->assertForbidden();
    }

    public function test_admin_adds_partner_and_rules_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $other = Customer::create(['mobile' => '09350000000', 'first_name' => 'سارا']);
        Livewire::test(ListWholesaleAccounts::class)->callAction('add', [
            'customer_id' => $other->id, 'shop_name' => 'دکور سارا', 'owner_name' => 'سارا', 'business_type' => 'decorator', 'city' => 'کرج',
        ])->assertHasNoActionErrors();
        $this->assertSame('approved', WholesaleAccount::query()->where('customer_id', $other->id)->value('status'));

        Livewire::test(ManageWholesaleRules::class)->callAction('create', [
            'scope' => 'category', 'target_id' => $this->kitchen->id, 'tiers' => [['min_qty' => 1, 'percent' => 12.5]], 'is_active' => true,
        ])->assertHasNoActionErrors();
        Livewire::test(ManageWholesaleRules::class)->callAction('create', ['scope' => 'category', 'target_id' => $this->kitchen->id, 'tiers' => []])
            ->assertHasActionErrors(['target_id' => 'unique']);   // one rule per category
        $this->get(route('filament.admin.resources.wholesale-rules.index'))->assertOk()->assertSee('آشپزخانه')->assertSee('۱۲٫۵', false);
        $this->get('/admin/settings')->assertOk()->assertSee('فروش عمده');
    }

    public function test_bulk_inquiry_goes_to_the_forms_inbox(): void
    {
        Auth::guard('customer')->logout();
        $form = Livewire::test(InquiryForm::class);
        $this->travel(5)->seconds();
        $form->set('name', 'شرکت آلفا')->set('mobile', '09351112233')->set('items', '۵۰ عدد ماگ برای هدیه‌ی پایان سال')
            ->call('submit')->assertHasNoErrors()->assertSet('sent', true);

        $submission = FormSubmission::query()->sole();
        $this->assertSame('wholesale_inquiry', $submission->form);
        $this->assertSame('۵۰ عدد ماگ برای هدیه‌ی پایان سال', $submission->data['items']);
    }
}
