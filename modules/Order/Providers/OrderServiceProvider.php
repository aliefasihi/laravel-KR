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
use Filament\Forms\Components\TextInput;
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
