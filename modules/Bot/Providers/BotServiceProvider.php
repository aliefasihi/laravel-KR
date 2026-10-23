<?php

/**
 * Khaneh Romantic — Bale + Telegram bot module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Modules\Bot\Console\BotPoll;
use Modules\Bot\Console\BotWebhook;
use Modules\Bot\Models\BotDraft;
use Modules\Bot\Support\Bot;
use Modules\Bot\Support\Channel;
use Modules\Bot\Support\Notifier;

use function Illuminate\Support\defer;

class BotServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(Bot::class);
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([BotPoll::class, BotWebhook::class]);
        }
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('bot:poll')->everyMinute()->withoutOverlapping(3)
                ->when(fn () => Settings::get('bot.mode', 'webhook') === 'polling' && Channel::all() !== []);
            $schedule->call(function () {
                DB::table('bot_updates')->where('created_at', '<', now()->subDays(3))->delete();
                DB::table('bot_messages')->where('created_at', '<', now()->subDays(120))->delete();
                BotDraft::query()->where('status', '!=', 'open')->where('updated_at', '<', now()->subDays(30))->get()->each->delete();
            })->daily()->name('bot:prune');
        });

        // new orders → everyone linked, in both apps (after the response: checkout never waits for Telegram)
        $notify = function (array $order, string $title): void {   // (a listener must not return false: it would stop the others)
            if (Channel::all() !== []) {
                defer(fn () => app(Notifier::class)->newOrder($order, $title));
            }
        };
        Event::listen('order.placed', function (array $order) use ($notify) {
            if ($order['status'] !== 'pending') {   // online payments are announced when paid
                $notify($order, '🛒 سفارش جدید');
            }
        });
        Event::listen('order.paid', function (array $order) use ($notify) {
            $notify($order, '💳 سفارش پرداخت شد');
        });

        Settings::section(new SettingsSection(
            key: 'bot',
            title: 'ربات بله و تلگرام',
            icon: 'heroicon-o-chat-bubble-left-right',
            sort: 61,
            defaults: ['telegram_enabled' => false, 'telegram_token' => null, 'telegram_proxy' => null, 'bale_enabled' => false, 'bale_token' => null, 'mode' => 'webhook'],
            schema: fn () => [
                Text::make('توکن را از @BotFather (تلگرام) و @botfather (بله) بگیرید. افراد را از صفحه‌ی «ربات» (تنظیمات ← ربات) وصل کنید.')->color('gray'),
                Fieldset::make('بله')->columns(2)->schema([
                    Toggle::make('bale_enabled')->label('روشن')->inline(false),
                    TextInput::make('bale_token')->label('توکن ربات بله')->password()->revealable()->extraInputAttributes(['dir' => 'ltr']),
                ]),
                Fieldset::make('تلگرام')->columns(2)->schema([
                    Toggle::make('telegram_enabled')->label('روشن')->inline(false),
                    TextInput::make('telegram_token')->label('توکن ربات تلگرام')->password()->revealable()->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('telegram_proxy')->label('پراکسی تلگرام (اگر سرور مستقیم به تلگرام نمی‌رسد)')->columnSpanFull()
                        ->extraInputAttributes(['dir' => 'ltr'])->placeholder('socks5h://1.2.3.4:1080  یا  http://user:pass@1.2.3.4:8080'),
                ]),
                Radio::make('mode')->label('دریافت پیام‌ها')->options([
                    'webhook' => 'وب‌هوک (فوری؛ بعد از ذخیره در صفحه‌ی «ربات» دکمه‌ی «اتصال» را بزنید)',
                    'polling' => 'پرسیدن مداوم (اگر تلگرام/بله به سرور دسترسی ندارد؛ به cron زمان‌بند نیاز دارد)',
                ]),
            ],
        ));
    }

    protected function registerRoutes(): void
    {
        parent::registerRoutes();

        Route::group([], $this->modulePath('routes/hook.php'));
    }
}
