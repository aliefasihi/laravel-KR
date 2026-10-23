<?php

/**
 * Khaneh Romantic — Web push (نوتیفیکیشن روی گوشی) module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\PersianText;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Modules\Push\Console\PushKeys;
use Modules\Push\Console\SendPush;
use Modules\Push\Models\PushSubscription;
use Modules\Push\Support\Push;
use Modules\Pwa\Http\Controllers\PwaController;

class PushServiceProvider extends ModuleServiceProvider
{
    /** order status → what the customer's phone shows */
    private const ORDER_MESSAGES = [
        'processing' => ['سفارش %s در حال آماده‌سازی است', 'به‌زودی بسته‌بندی و ارسال می‌شود.'],
        'shipped' => ['سفارش %s ارسال شد 🚚', 'کد رهگیری: %s'],
        'completed' => ['سفارش %s تحویل شد', 'امیدواریم دوستش داشته باشید؛ نظرتان را برایمان بنویسید.'],
        'cancelled' => ['سفارش %s لغو شد', 'برای جزئیات، صفحه‌ی سفارش را ببینید.'],
    ];

    public function register(): void
    {
        parent::register();

        $this->app->scoped(Push::class);
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([SendPush::class, PushKeys::class]);
        }
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('push:send')->everyMinute()->withoutOverlapping(5)->when(fn () => Push::enabled());
        });

        StorefrontHooks::add('body.end', fn () => Push::enabled() ? view('push::partials.config') : '', 80);
        StorefrontHooks::add('account.order.actions', fn (array $data) => Push::enabled() ? view('push::partials.order-button', $data) : '', 60);

        Event::listen('order.status_changed', function (array $order) {
            if ($order['status'] !== 'shipped') {   // «shipped» comes with its own event (with the tracking code)
                $this->orderUpdate($order);
            }
        });
        Event::listen('order.shipped', fn (array $order) => $this->orderUpdate($order));

        Settings::section(new SettingsSection(
            key: 'push',
            title: 'نوتیفیکیشن',
            icon: 'heroicon-o-bell-alert',
            sort: 36,
            defaults: [
                'enabled' => false, 'prompt' => true, 'prompt_views' => 2, 'prompt_delay' => 15,
                'prompt_title' => null, 'prompt_text' => null, 'order_updates' => true,
            ],
            schema: fn () => [
                Text::make(fn () => self::status())->color('gray'),
                Toggle::make('enabled')->label('نوتیفیکیشن روشن باشد')
                    ->helperText('به «اپلیکیشن (PWA)» روشن نیاز دارد. ارسال با زمان‌بند (cron) دقیقه‌ای یک بار انجام می‌شود.'),
                Fieldset::make('درخواست اجازه از بازدیدکننده')->columns(2)->schema([
                    Toggle::make('prompt')->label('کارت «از تخفیف‌ها جا نمانید» نشان داده شود')->inline(false)
                        ->helperText('اول کارت خود سایت؛ پنجره‌ی اجازه‌ی مرورگر فقط بعد از «بله». با «بعداً» تا ۱۴ روز نمی‌آید.'),
                    Toggle::make('order_updates')->label('خبر وضعیت سفارش برای مشتری (آماده‌سازی، ارسال، تحویل، لغو)')->inline(false),
                    TextInput::make('prompt_views')->label('از چندمین صفحه‌ای که می‌بیند')->integer()->minValue(1)->maxValue(20),
                    TextInput::make('prompt_delay')->label('بعد از چند ثانیه در صفحه')->integer()->minValue(0)->maxValue(120)->suffix('ثانیه'),
                    TextInput::make('prompt_title')->label('عنوان کارت')->placeholder('از تخفیف‌ها جا نمانید')->maxLength(60),
                    TextInput::make('prompt_text')->label('متن کارت')->maxLength(160)
                        ->placeholder('حراج‌ها، کالکشن‌های تازه و خبر سفارش‌تان را با نوتیفیکیشن بگیرید. هر وقت خواستید خاموشش کنید.'),
                ]),
            ],
        ));
    }

    protected function registerRoutes(): void
    {
        parent::registerRoutes();

        Route::group([], $this->modulePath('routes/worker.php'));   // service worker calls: no session
    }

    private function orderUpdate(array $order): void
    {
        $message = self::ORDER_MESSAGES[$order['status']] ?? null;
        if (! $message || ! $order['customer_id'] || ($order['notify'] ?? true) === false || ! Settings::get('push.order_updates', true)) {
            return;
        }
        $number = PersianText::toPersianDigits((string) $order['number']);
        $body = $order['status'] === 'shipped'
            ? ($order['tracking_code'] ? sprintf($message[1], (string) $order['tracking_code']) : 'به‌زودی به دستتان می‌رسد.')
            : $message[1];

        app(Push::class)->toCustomer((int) $order['customer_id'], [
            'title' => sprintf($message[0], $number),
            'body' => $body,
            'url' => route('customer.orders.show', $order['id'], absolute: false).'?utm_source=push',
            'tag' => 'order-'.$order['id'],
        ]);
    }

    private static function status(): string
    {
        if (! PwaController::enabled()) {
            return 'اول «اپلیکیشن (PWA)» را در همین تنظیمات روشن کنید؛ نوتیفیکیشن روی آن کار می‌کند.';
        }
        $subs = PushSubscription::query()->count();
        $waiting = DB::table('push_outbox')->count();

        return PersianText::toPersianDigits($subs).' مرورگر عضو، '.PersianText::toPersianDigits($waiting).' پیام در صف ارسال. پیام گروهی: «بازاریابی ← نوتیفیکیشن‌ها».';
    }
}
