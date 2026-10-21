<?php

/**
 * Khaneh Romantic — Order module: checkout, orders, order SMS, admin orders & reports
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Providers;

use App\Core\Modules\Modules;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Coupon\Support\CouponEvaluator;
use Modules\Integration\Models\OutboxEvent;
use Modules\Order\Http\Middleware\RememberTrafficSource;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Payments\PaymentMethods;
use Modules\Order\Support\OrderNotifier;
use Modules\Order\Support\OrderWorkflow;
use Modules\Order\Support\PlaceOrder;
use Modules\Order\Support\Stock;
use Modules\Order\Support\TrafficSource;

class OrderServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(Stock::class);
        $this->app->scoped(OrderWorkflow::class);
        $this->app->scoped(PlaceOrder::class);
        $this->app->scoped(OrderNotifier::class);
        PaymentMethods::flush(); // the Payment module registers its methods in boot()
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('order.checkout', Checkout::class);

        // sales channel: where the shopper came from (Torob, Google, an SMS link…) → orders.source
        $this->app['router']->pushMiddlewareToGroup('web', RememberTrafficSource::class);
        Event::listen('order.placing', function (Order $order) {
            if (($order->source ?? 'web') === 'web' && ($source = TrafficSource::current())) {
                $order->forceFill(['source' => $source])->save();
            }
        });

        $this->notifications();
        $this->settings();
        $this->printSettings();
        $this->integrations();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('orders:cancel-unpaid')->everyFiveMinutes()->withoutOverlapping();
        });
    }

    private function notifications(): void
    {
        $notify = fn (string $event) => fn (array $order) => app(OrderNotifier::class)->handle($event, $order);

        // «ثبت سفارش» only for orders that don't wait for an online payment (those get «پرداخت موفق»)
        // (a block, not `cond && …`: a listener returning false would stop every listener after it)
        Event::listen('order.placed', function (array $order) use ($notify) {
            if ($order['status'] !== 'pending') {
                $notify('placed')($order);
            }
        });
        Event::listen('order.paid', $notify('paid'));
        Event::listen('order.shipped', $notify('shipped'));
        Event::listen('order.status_changed', function (array $order) use ($notify) {
            if (in_array($order['status'], ['completed', 'cancelled'], true)) {
                $notify($order['status'])($order);
            }
        });
    }

    private function settings(): void
    {
        Settings::section(new SettingsSection(
            key: 'order_sms',
            title: 'پیامک سفارش',
            icon: 'heroicon-o-bell-alert',
            sort: 35,
            defaults: ['admin_mobiles' => null, 'admin_template' => null] + array_fill_keys(array_map(fn ($e) => $e.'_template', array_keys(OrderNotifier::EVENTS)), null),
            schema: fn () => [
                Text::make('برای هر مرحله نام الگو (کاوه‌نگار) یا شناسه‌ی قالب (sms.ir) را بنویسید؛ خالی = بدون پیامک. مقادیر الگو به ترتیب: ORDER (شماره سفارش)، AMOUNT (مبلغ)، TRACKING (کد رهگیری)، NAME (نام مشتری).')->color('gray'),
                Fieldset::make('پیامک به مشتری')->schema(array_map(
                    fn (string $event, string $label) => TextInput::make($event.'_template')->label($label)->extraInputAttributes(['dir' => 'ltr']),
                    array_keys(OrderNotifier::EVENTS), OrderNotifier::EVENTS,
                ))->columns(2),
                Fieldset::make('پیامک سفارش تازه به فروشگاه')->schema([
                    TextInput::make('admin_mobiles')->label('شماره‌ها')->placeholder('09121234567, 09351234567')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('admin_template')->label('الگو')->extraInputAttributes(['dir' => 'ltr']),
                ])->columns(2),
            ],
        ));
    }

    private function printSettings(): void
    {
        Settings::section(new SettingsSection(
            key: 'print',
            title: 'فاکتور و برچسب',
            icon: 'heroicon-o-printer',
            sort: 37,
            defaults: [
                'sender_name' => null, 'sender_phone' => null, 'sender_postal_code' => null, 'sender_address' => null,
                'label_size' => 'a6', 'show_logo' => true, 'customer_invoice' => true,
                'national_id' => null, 'economic_code' => null, 'registration_no' => null, 'invoice_note' => null,
            ],
            schema: fn () => [
                Fieldset::make('فرستنده روی برچسب پستی')->columns(2)->schema([
                    TextInput::make('sender_name')->label('نام فرستنده')->placeholder(config('khaneh.store.name')),
                    TextInput::make('sender_phone')->label('تلفن فرستنده')->placeholder(config('khaneh.store.mobile'))->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('sender_postal_code')->label('کد پستی فرستنده')->maxLength(10)->extraInputAttributes(['dir' => 'ltr']),
                    Textarea::make('sender_address')->label('نشانی فرستنده')->placeholder(config('khaneh.store.address'))->rows(2)->columnSpanFull(),
                    Radio::make('label_size')->label('اندازه‌ی برچسب')->columnSpanFull()->options([
                        'a6' => 'A6 / ۱۰×۱۵ — هر برچسب یک صفحه (پرینتر حرارتی برچسب)',
                        'a4' => 'چهار برچسب روی یک A4 (پرینتر معمولی؛ بعد از چاپ برش بزنید)',
                    ]),
                ]),
                Fieldset::make('فاکتور')->columns(3)->schema([
                    TextInput::make('national_id')->label('شناسه‌ی ملی')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('economic_code')->label('کد اقتصادی')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('registration_no')->label('شماره‌ی ثبت')->extraInputAttributes(['dir' => 'ltr']),
                    Textarea::make('invoice_note')->label('متن پایین فاکتور')->rows(2)->columnSpanFull()
                        ->placeholder('مثلاً: تا ۷ روز امکان مرجوعی کالای سالم در بسته‌بندی اصلی وجود دارد.'),
                    Toggle::make('show_logo')->label('لوگو روی فاکتور')->columnSpanFull(),
                    Toggle::make('customer_invoice')->label('مشتری بتواند فاکتور سفارشش را از حساب کاربری چاپ کند')->columnSpanFull(),
                ]),
            ],
        ));
    }

    private function integrations(): void
    {
        // first-order-only coupons need the shopper's order history
        if (class_exists(CouponEvaluator::class) && Modules::enabled('Coupon')) {
            CouponEvaluator::countPreviousOrdersUsing(fn (?int $customerId, ?string $mobile) => Order::query()
                ->whereNotIn('status', ['cancelled', 'failed'])
                ->where(fn ($q) => $q->when($customerId, fn ($w) => $w->orWhere('customer_id', $customerId))
                    ->when($mobile, fn ($w) => $w->orWhere('customer_mobile', $mobile)))
                ->count());
        }

        // super-app / n8n: order events into the outbox (Integration module)
        if (Modules::enabled('Integration')) {
            foreach (['order.placed', 'order.paid', 'order.status_changed', 'order.refunded'] as $event) {
                Event::listen($event, fn (array $order) => OutboxEvent::record($event, $order));
            }
        }
    }
}
