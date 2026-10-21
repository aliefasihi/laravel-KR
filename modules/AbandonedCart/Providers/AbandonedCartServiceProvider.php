<?php

/**
 * Khaneh Romantic — Abandoned-cart reminder module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\AbandonedCart\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\AbandonedCart\Support\Reminders;
use Modules\Checkout\Support\Cart;

class AbandonedCartServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $snapshot = function (array $items) {
            $customer = auth('customer')->user();
            if ($customer) {
                app(Reminders::class)->remember($customer->getAuthIdentifier(), $items, $items === [] ? 0 : app(Cart::class)->subtotal());
            }
        };
        Event::listen('cart.updated', fn (array $items) => rescue(fn () => $snapshot($items), report: true));
        // signing in with a full cart (usually on the way to checkout)
        Event::listen(Login::class, function (Login $login) use ($snapshot) {
            if ($login->guard === 'customer' && ($items = app(Cart::class)->items()) !== []) {
                rescue(fn () => $snapshot($items), report: true);
            }
        });
        Event::listen('order.placed', fn (array $order) => rescue(fn () => app(Reminders::class)->ordered($order), report: true));

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(fn () => app(Reminders::class)->sendDue())->everyFifteenMinutes()->name('abandoned:remind')->withoutOverlapping();
        });

        Settings::section(new SettingsSection(
            key: 'abandoned',
            title: 'سبد رهاشده',
            icon: 'heroicon-o-shopping-cart',
            sort: 37,
            defaults: ['enabled' => true, 'template' => null, 'delay_minutes' => 120, 'min_toman' => 0, 'coupon_code' => null],
            schema: fn () => [
                Toggle::make('enabled')->label('پیامک یادآوری سبد خرید فرستاده شود'),
                Text::make('فقط برای مشتری‌هایی که وارد حسابشان شده‌اند (برای رفتن به پرداخت ورود لازم است). یک پیامک برای هر سبد، شب‌ها نه. مقادیر الگو به ترتیب: LINK (لینک برگرداندن سبد) · CODE (کد تخفیف یا -) · COUNT (تعداد کالا) · NAME (نام؛ در کاوه‌نگار token10).')->color('gray'),
                TextInput::make('template')->label('نام الگو (کاوه‌نگار) / شناسه قالب (sms.ir)')->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('نمونه: %token10% عزیز، %token3% کالا در سبد خریدت منتظرته. با کد %token2% تخفیف بگیر: %token%'),
                TextInput::make('delay_minutes')->label('چند دقیقه بعد از آخرین تغییر سبد')->numeric()->minValue(15)->required(),
                TextInput::make('min_toman')->label('حداقل مبلغ سبد (تومان)')->numeric()->minValue(0)->helperText('سبدهای کوچک‌تر پیامک نمی‌گیرند. ۰ = همه.'),
                TextInput::make('coupon_code')->label('کد تخفیف (اختیاری)')->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('یک کد که در «کدهای تخفیف» ساخته‌اید؛ در پیامک می‌آید و با لینک خودکار روی سبد اعمال می‌شود.'),
            ],
        ));
    }
}
