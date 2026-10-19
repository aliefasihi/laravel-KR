<?php

/**
 * Khaneh Romantic — Customer club (wallet, points, levels, referrals)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Hooks into: the pricing pipeline (wallet as the last step), order events (placing, status,
 * refunds), payment methods (all-from-wallet), customer sign-up (referrals), storefront hooks
 * (cart/checkout switch, account card, product points) and the settings page.
 */

namespace Modules\Loyalty\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Checkout\Support\Pricing\Quoter;
use Modules\Customer\Models\Customer;
use Modules\Loyalty\Http\Middleware\CaptureReferral;
use Modules\Loyalty\Livewire\Club;
use Modules\Loyalty\Livewire\WalletToggle;
use Modules\Loyalty\Models\PointsTransaction;
use Modules\Loyalty\Models\WalletTransaction;
use Modules\Loyalty\Payments\WalletPayment;
use Modules\Loyalty\Support\Loyalty;
use Modules\Loyalty\Support\OrderLoyalty;
use Modules\Loyalty\Support\Referrals;
use Modules\Loyalty\Support\Wallet;
use Modules\Order\Models\Order;
use Modules\Order\Payments\PaymentMethods;

class LoyaltyServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Customer::resolveRelationUsing('loyaltyWalletTransactions', fn (Customer $customer) => $customer->hasMany(WalletTransaction::class, 'customer_id'));
        Customer::resolveRelationUsing('loyaltyPointsTransactions', fn (Customer $customer) => $customer->hasMany(PointsTransaction::class, 'customer_id'));

        Livewire::component('loyalty.club', Club::class);
        Livewire::component('loyalty.wallet-toggle', WalletToggle::class);
        $this->app->make(Router::class)->pushMiddlewareToGroup('web', CaptureReferral::class);

        // wallet: last pricing step, so it pays what is left after discounts and shipping
        Quoter::extend('wallet', function (QuoteContext $context, Quote $quote) {
            if (! $context->customerId || ! Loyalty::on('wallet') || ! Loyalty::get('wallet_checkout') || ! session(WalletToggle::SESSION_KEY)) {
                return;
            }
            $balance = Wallet::balance((int) $context->customerId);
            if ($balance > 0 && ($used = $quote->addCredit('wallet', 'پرداخت از کیف پول', $balance)) > 0) {
                $quote->meta['wallet'] = $used;
            }
        }, priority: 90);
        PaymentMethods::register(new WalletPayment, 5);

        Event::listen('order.placing', fn (Order $order, Quote $quote) => OrderLoyalty::placing($order, $quote));
        Event::listen('order.status_changed', fn (array $payload) => OrderLoyalty::statusChanged($payload));
        Event::listen('order.refunded', fn (array $payload) => OrderLoyalty::refunded($payload));
        Event::listen('eloquent.created: '.Customer::class, function (Customer $customer) {
            if ($code = request()->cookie(Referrals::COOKIE)) {
                rescue(fn () => Referrals::attach($customer, $code), report: true);
                cookie()->queue(cookie()->forget(Referrals::COOKIE));
            }
        });

        StorefrontHooks::add('cart.summary', fn () => auth('customer')->check() && Loyalty::on('wallet') ? view('loyalty::partials.wallet-toggle') : null, 20);
        StorefrontHooks::add('account.dashboard', 'loyalty::partials.dashboard-card', 10);
        StorefrontHooks::add('product.buybox', fn (array $data) => Loyalty::on('points') ? view('loyalty::partials.product-points', $data) : null);

        Settings::section(new SettingsSection(
            key: 'loyalty',
            title: 'باشگاه مشتریان',
            icon: 'heroicon-o-gift',
            sort: 35,
            defaults: Loyalty::DEFAULTS,
            schema: fn () => [
                Fieldset::make('کیف پول')->columns(2)->schema([
                    Toggle::make('wallet_enabled')->label('کیف پول فعال'),
                    Toggle::make('wallet_checkout')->label('پرداخت با کیف پول در تسویه حساب'),
                ]),
                Fieldset::make('امتیاز خرید')->columns(2)->schema([
                    Toggle::make('points_enabled')->label('امتیاز فعال')->columnSpanFull(),
                    TextInput::make('points_earn_per')->label('هر چند تومان خرید = ۱ امتیاز')->numeric()->minValue(1)->suffix('تومان')
                        ->helperText('روی جمع کالاها پس از تخفیف، بدون هزینه‌ی ارسال؛ وقتی سفارش «تکمیل شده» شود.'),
                    TextInput::make('points_value')->label('ارزش هر امتیاز')->numeric()->minValue(1)->suffix('تومان اعتبار'),
                    TextInput::make('points_min_redeem')->label('حداقل امتیاز برای تبدیل')->numeric()->minValue(0),
                    TextInput::make('points_max_redeem')->label('حداکثر امتیاز در هر تبدیل (خالی = بدون سقف)')->numeric()->minValue(1),
                ]),
                Fieldset::make('سطح‌ها')->schema([
                    Toggle::make('levels_enabled')->label('سطح‌بندی مشتریان فعال (تعریف سطح‌ها: باشگاه مشتریان ← سطح‌ها)'),
                ]),
                Fieldset::make('معرفی دوستان')->columns(2)->schema([
                    Toggle::make('referral_enabled')->label('معرفی دوستان فعال')->columnSpanFull(),
                    TextInput::make('referral_referrer_reward')->label('هدیه‌ی معرف')->numeric()->minValue(0)->suffix('تومان'),
                    TextInput::make('referral_referred_reward')->label('هدیه‌ی دوستِ معرفی‌شده')->numeric()->minValue(0)->suffix('تومان'),
                    TextInput::make('referral_min_order')->label('حداقل اولین خرید دوست')->numeric()->minValue(0)->suffix('تومان'),
                ]),
                Toggle::make('sms_notify')->label('پیامک امتیاز و هدیه‌ی معرفی به مشتری'),
            ],
        ));
    }
}
