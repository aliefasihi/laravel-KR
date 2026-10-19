<?php

/**
 * Customer club: wallet ledger, paying with the wallet (part / all), points on completion with the
 * level multiplier, refund reversals, redeem, levels, referrals, account and admin pages.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use LogicException;
use Modules\Checkout\Support\Cart;
use Modules\Customer\Livewire\Login;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Invites;
use Modules\Customer\Support\Otp;
use Modules\Loyalty\Filament\Resources\Members\Pages\ListMembers;
use Modules\Loyalty\Filament\Resources\Members\Pages\ViewMember;
use Modules\Loyalty\Livewire\Club;
use Modules\Loyalty\Livewire\WalletToggle;
use Modules\Loyalty\Models\Level;
use Modules\Loyalty\Models\PointsTransaction;
use Modules\Loyalty\Models\Referral;
use Modules\Loyalty\Models\WalletTransaction;
use Modules\Loyalty\Support\InsufficientBalance;
use Modules\Loyalty\Support\Levels;
use Modules\Loyalty\Support\Points;
use Modules\Loyalty\Support\Referrals;
use Modules\Loyalty\Support\Wallet;
use Modules\Order\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Support\OrderWorkflow;
use Modules\Order\Tests\Feature\ShopTestCase;

class LoyaltyTest extends ShopTestCase
{
    /** cart: pan 84,500,000 + dish 29,500,000 = 114,000,000 Rials; post shipping is free over 50M */
    private function order(string $payment = 'cod', bool $wallet = false): Order
    {
        $this->fillCart();
        if ($wallet) {
            Livewire::test(WalletToggle::class)->set('use', true);
        }
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', $payment)->call('place')->assertHasNoErrors();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function complete(Order $order): Order
    {
        app(OrderWorkflow::class)->changeStatus($order, 'completed');

        return $order->refresh();
    }

    public function test_wallet_ledger_is_atomic_never_negative_and_immutable(): void
    {
        $id = $this->customer->id;
        $credit = Wallet::credit($id, 5_000_000, 'gift', ['description' => 'هدیه‌ی یلدا']);
        $this->assertSame([0, 5_000_000], [$credit->balance_before, $credit->balance_after]);
        Wallet::debit($id, 2_000_000, 'admin_adjustment');
        $this->assertSame(3_000_000, Wallet::balance($id));

        try {
            Wallet::debit($id, 3_000_001, 'admin_adjustment');
            $this->fail('debit over balance');
        } catch (InsufficientBalance) {
        }
        $this->assertSame(3_000_000, Wallet::balance($id));
        $this->assertSame(2, WalletTransaction::query()->count());

        $partial = Wallet::debitUpTo($id, 9_000_000, 'referral_reversal');
        $this->assertSame(3_000_000, $partial->amount);           // only what there was
        $this->assertSame(0, Wallet::balance($id));
        $this->assertNull(Wallet::debitUpTo($id, 1_000, 'referral_reversal'));

        $this->expectException(LogicException::class);
        $credit->update(['amount' => 1]);
    }

    public function test_paying_part_with_the_wallet_and_getting_it_back_on_cancel(): void
    {
        Wallet::credit($this->customer->id, 20_000_000, 'gift');
        $this->fillCart();
        $this->get('/checkout')->assertOk()->assertSee('پرداخت با کیف پول')->assertSee('۲٬۰۰۰٬۰۰۰');

        app(Cart::class)->clear();
        $order = $this->order('cod', wallet: true);
        $this->assertSame(20_000_000, $order->wallet_used);
        $this->assertSame(114_000_000 - 20_000_000, $order->total);    // what's left to pay
        $this->assertSame(0, Wallet::balance($this->customer->id));
        $this->assertSame('order_payment', WalletTransaction::query()->latest('id')->value('reason'));
        $this->get('/account/orders/'.$order->number)->assertSee('پرداخت از کیف پول')->assertSee('باقی‌مانده‌ی قابل پرداخت');

        app(OrderWorkflow::class)->changeStatus($order, 'cancelled');
        $this->assertSame(20_000_000, Wallet::balance($this->customer->id));
        $this->assertSame(20_000_000, $order->fresh()->wallet_refunded);
        app(OrderWorkflow::class)->changeStatus($order->fresh(), 'cancelled');   // no double refund
        $this->assertSame(20_000_000, Wallet::balance($this->customer->id));
    }

    public function test_wallet_covering_everything_pays_the_order_by_itself(): void
    {
        Wallet::credit($this->customer->id, 200_000_000, 'gift');
        $this->fillCart();
        Livewire::test(WalletToggle::class)->set('use', true);
        $checkout = Livewire::test(Checkout::class)->set('shippingMethod', 'post');
        $this->assertSame(['wallet'], array_keys($checkout->viewData('payments')));   // other methods hidden at zero
        $checkout->assertSet('paymentMethod', 'wallet')->call('place')->assertHasNoErrors();

        $order = Order::query()->sole();
        $this->assertSame(0, $order->total);
        $this->assertSame(114_000_000, $order->wallet_used);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(86_000_000, Wallet::balance($this->customer->id));
    }

    public function test_points_on_completion_with_level_multiplier_reversal_redeem_and_debt(): void
    {
        $order = $this->complete($this->order());
        // 114,000,000 Rials / 100,000 per point = 1,140 points at bronze (×1)
        $this->assertSame(1140, Points::balance($this->customer->id));
        $this->assertSame(114_000_000, Levels::totalSpent($this->customer->id));
        $this->assertSame('برنزی', Levels::current($this->customer->id)->name);
        $this->assertTrue($order->notes()->where('body', 'like', '%امتیاز باشگاه%')->exists());

        // completing again never earns twice
        app(OrderWorkflow::class)->changeStatus($order, 'processing');
        $this->complete($order);
        $this->assertSame(1140, Points::balance($this->customer->id));
        $this->assertSame(1, PointsTransaction::query()->where('type', 'earn')->count());

        // the second order still earns at bronze (its own amount doesn't raise its multiplier)…
        $this->variant('GK-7710')->update(['stock' => 10]);
        $second = $this->complete($this->order());
        $this->assertSame(2280, Points::balance($this->customer->id));
        $this->assertSame('نقره‌ای', Levels::current($this->customer->id)->name);   // 228M ≥ 200M
        // …the third at silver ×1.25
        $third = $this->complete($this->order());
        $this->assertSame(2280 + 1425, Points::balance($this->customer->id));

        // redeem: minimum, then into the wallet
        Settings::set(['loyalty.points_min_redeem' => 500, 'loyalty.points_value' => 100]);   // 1 point = 100 Toman
        $club = Livewire::test(Club::class)->set('redeem', '۴۰۰')->call('convert')->assertHasErrors('redeem')
            ->set('redeem', '3000')->call('convert')->assertHasNoErrors();
        $this->assertSame(705, Points::balance($this->customer->id));
        $this->assertSame(3000 * 1000, Wallet::balance($this->customer->id));
        $club->set('redeem', '800')->call('convert')->assertHasErrors('redeem');        // more than there is

        // refunding the third order takes its 1,425 points back: the balance goes negative (debt)
        app(OrderWorkflow::class)->refund($third->load('items'), $third->total);
        $this->assertSame(705 - 1425, Points::balance($this->customer->id));
        $this->assertSame('نقره‌ای', Levels::current($this->customer->id)->name);    // back to 228M
        $club->set('redeem', '1')->call('convert')->assertHasErrors('redeem');          // nothing while negative
        app(OrderWorkflow::class)->refund($second->load('items'), $second->total);
        $this->assertSame('برنزی', Levels::current($this->customer->id)->name);        // levels go down too

        // the next purchase pays the debt off first
        $this->complete($this->order());
        $this->assertSame(705 - 1425 - 1140 + 1140, Points::balance($this->customer->id));

        // rebuild from orders gives the same totals
        Levels::rebuild();
        $this->assertSame(228_000_000, Levels::totalSpent($this->customer->id));
    }

    public function test_partial_refund_returns_the_wallet_share_and_refund_to_wallet(): void
    {
        Wallet::credit($this->customer->id, 14_000_000, 'gift');
        $order = $this->order('cod', wallet: true);                    // 14M wallet + 100M cash
        app(OrderWorkflow::class)->markPaid($order, 'COD');

        // half of the cash back → half of the wallet share back
        app(OrderWorkflow::class)->refund($order->fresh()->load('items'), 50_000_000);
        $this->assertSame(7_000_000, Wallet::balance($this->customer->id));

        // the rest back as store credit (no bank transfer): cash part + the remaining wallet share
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ViewOrder::class, ['record' => $order->number])
            ->callAction('refund', ['amount' => 5_000_000, 'to_wallet' => true, 'reason' => 'مرجوعی'])->assertHasNoActionErrors();
        $this->assertSame(7_000_000 + 50_000_000 + 7_000_000, Wallet::balance($this->customer->id));
        $this->assertSame(14_000_000, $order->fresh()->wallet_refunded);
        $this->assertSame('refund_to_wallet', WalletTransaction::query()->where('reason', 'refund_to_wallet')->value('reason'));
    }

    public function test_referral_link_signup_reward_and_reversal(): void
    {
        $code = Referrals::codeFor($this->customer->id);
        $this->assertMatchesRegularExpression('/^KR[A-Z2-9]{5}$/', $code);
        $this->assertSame($code, Referrals::codeFor($this->customer->id));

        Auth::guard('customer')->logout();
        $this->get('/?ref='.strtolower($code))->assertOk()->assertCookie(Referrals::COOKIE, $code);
        $this->app['cookie']->flushQueuedCookies();   // (one app across test requests)
        $this->get('/?ref=NOPE123')->assertCookieMissing(Referrals::COOKIE);

        // the visitor signs up (the cookie comes back with the sign-up request)
        app()->instance('request', Request::create('/account/login', 'POST', [], [Referrals::COOKIE => $code]));
        $sara = Customer::create(['mobile' => '09125556677', 'first_name' => 'سارا']);
        $referral = Referral::query()->sole();
        $this->assertSame([$this->customer->id, $sara->id, 'pending'], [$referral->referrer_id, $referral->referred_id, $referral->status]);

        // no self referral, set once
        $this->assertNull(Referrals::attach($this->customer, $code));
        $this->assertNull(Referrals::attach($sara, Referrals::codeFor($this->customer->id)));

        // Sara's first order (≥ 2,000,000 Toman) is completed → both get their gift
        $sara->addresses()->create(['recipient_name' => 'سارا', 'recipient_mobile' => '09125556677', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'ونک']);
        Auth::guard('customer')->login($sara);
        $order = $this->complete($this->order());
        $this->assertSame('rewarded', $referral->fresh()->status);
        $this->assertSame(2_000_000, Wallet::balance($this->customer->id));
        $this->assertSame(1_000_000, Wallet::balance($sara->id));

        // a second order rewards nothing; refunding the qualifying one takes both back
        Wallet::debit($sara->id, 600_000, 'admin_adjustment');
        app(OrderWorkflow::class)->changeStatus($order, 'refunded');
        $this->assertSame('reversed', $referral->fresh()->status);
        $this->assertSame(0, Wallet::balance($this->customer->id));
        $this->assertSame(0, Wallet::balance($sara->id));              // only what was left (400,000)
    }

    public function test_invite_code_on_the_sign_up_form_and_later_in_the_club(): void
    {
        $code = Referrals::codeFor($this->customer->id);
        Auth::guard('customer')->logout();
        $this->get('/account/login')->assertSee('کد معرف دارید؟');

        // typed with the Persian layout still on (ن ق = K R) and Persian digits → still found
        $persian = strtr(strtolower($code), ['k' => 'ن', 'r' => 'ق', 'a' => 'ش', 's' => 'س', 'd' => 'ی', 'f' => 'ب', 'm' => 'پ', 'x' => 'ط']);
        $persian = strtr($persian, ['2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
        $this->assertSame($code, Referrals::find(' '.$persian.' '));
        $this->assertNull(Referrals::find('KRZZZZZ'));

        // unknown code → error on the first step, no SMS
        $login = Livewire::test(Login::class)->set('mobile', '09125556677')->set('invite', 'KRZZZZZ')->call('send')
            ->assertHasErrors('invite')->assertSet('step', 'mobile');
        $login->set('invite', strtolower($code))->call('send')->assertHasNoErrors()->assertSet('step', 'code')->assertSet('invite', $code);
        $hash = (new \ReflectionMethod(Otp::class, 'hash'))->invoke(app(Otp::class), '09125556677', '12345');
        DB::table('customer_otps')->where('mobile', '09125556677')->update(['code_hash' => $hash]);   // a known code
        $login->set('code', '۱۲۳۴۵')->call('verify')->assertHasNoErrors();

        $sara = Customer::query()->where('mobile', '09125556677')->sole();
        $this->assertSame([$this->customer->id, 'pending'], [Referral::query()->where('referred_id', $sara->id)->value('referrer_id'), Referral::query()->where('referred_id', $sara->id)->value('status')]);

        // the link cookie is prefilled, but what the person types wins
        $other = Customer::create(['mobile' => '09127778899']);
        $otherCode = Referrals::codeFor($other->id);
        Auth::guard('customer')->logout();
        $this->withCookie(Referrals::COOKIE, $code)->get('/account/login')->assertOk()->assertSee('&quot;invite&quot;:&quot;'.$code.'&quot;', false);
        app(Invites::class)->forSignup($otherCode, fn () => Customer::create(['mobile' => '09121212121']));
        $this->assertSame($other->id, Referral::query()->where('referred_id', Customer::query()->where('mobile', '09121212121')->value('id'))->value('referrer_id'));

        // forgot it at sign-up: the club page takes it for a few days, before a completed order
        app()->instance('request', Request::create('/account/login', 'POST'));   // (no link cookie this time)
        $nima = Customer::create(['mobile' => '09124443322', 'first_name' => 'نیما']);
        Auth::guard('customer')->login($nima);
        Livewire::test(Club::class)->assertSee('کد معرف دارید؟')
            ->set('inviteCode', Referrals::codeFor($nima->id))->call('enterInvite')->assertHasErrors('inviteCode')
            ->set('inviteCode', 'KRZZZZZ')->call('enterInvite')->assertHasErrors('inviteCode')
            ->set('inviteCode', $code)->call('enterInvite')->assertHasNoErrors()->assertDontSee('کد معرف دارید؟')->assertSee('شما با دعوت');
        $this->assertSame($this->customer->id, Referral::query()->where('referred_id', $nima->id)->value('referrer_id'));

        // too late: older account, or one with a completed order
        $old = Customer::create(['mobile' => '09126665544']);
        $old->forceFill(['created_at' => now()->subDays(8)])->save();
        $this->assertFalse(Referrals::canEnter($old));
        $this->assertNull(Referrals::enter($old, $code));
        Settings::set(['loyalty.referral_late_days' => 0]);
        $this->assertFalse(Referrals::canEnter(Customer::create(['mobile' => '09126665500'])));
    }

    public function test_account_club_product_hint_and_admin_pages(): void
    {
        Wallet::credit($this->customer->id, 25_000_000, 'gift');
        Points::adjust($this->customer->id, 1200, 'هدیه', null);

        $this->get('/account')->assertOk()->assertSee('باشگاه مشتریان')->assertSee('۲٬۵۰۰٬۰۰۰');
        $this->get('/account/club')->assertOk()
            ->assertSee('موجودی کیف پول')->assertSee('۱٬۲۰۰')->assertSee('دوستانتان را دعوت کنید')
            ->assertSee(Referrals::codeFor($this->customer->id))->assertSee('سطح‌های باشگاه')->assertSee('نقره‌ای');
        Livewire::test(Club::class)->set('history', 'points')->assertSee('تغییر توسط فروشگاه');

        $this->get($this->variant('GK-7710')->product->url())->assertOk()->assertSee('امتیاز</b> باشگاه مشتریان', false);

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['/admin/loyalty/members', '/admin/loyalty/members/'.$this->customer->id, '/admin/loyalty/wallet', '/admin/loyalty/points', '/admin/loyalty/levels', '/admin/loyalty/referrals'] as $url) {
            $this->get($url)->assertOk();
        }
        Livewire::test(ListMembers::class)->assertCanSeeTableRecords([$this->customer])
            ->callTableAction('wallet', $this->customer, ['type' => 'debit', 'amount' => 500_000, 'description' => 'اصلاح'])
            ->assertHasNoTableActionErrors();
        $this->assertSame(20_000_000, Wallet::balance($this->customer->id));
        Livewire::test(ViewMember::class, ['record' => $this->customer->id])
            ->callAction('points', ['type' => 'sub', 'points' => 200, 'description' => 'اصلاح'])->assertHasNoActionErrors();
        $this->assertSame(1000, Points::balance($this->customer->id));

        // editing a level moves customers between levels
        Levels::addOrder(tap($this->order(), fn ($o) => $o->update(['status' => 'completed'])));
        Level::query()->where('name', 'نقره‌ای')->update(['min_spent' => 100_000_000]);
        Level::query()->where('name', 'نقره‌ای')->first()->save();
        $this->assertSame('نقره‌ای', Level::find(DB::table('loyalty_customer_levels')->where('customer_id', $this->customer->id)->value('level_id'))->name);
    }
}
