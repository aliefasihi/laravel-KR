<?php

/**
 * Khaneh Romantic — Gift wrap and card message
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\GiftWrap\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use App\Core\Support\StorefrontHooks;
use App\Filament\Forms\TomanInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Checkout\Support\Pricing\Quoter;
use Modules\GiftWrap\Livewire\GiftOption;
use Modules\GiftWrap\Models\OrderGift;
use Modules\GiftWrap\Support\Gift;
use Modules\Order\Support\ReportMetrics;

class GiftWrapServiceProvider extends ModuleServiceProvider
{
    /** views as gift::… (shorter than giftwrap::) */
    public function key(): string
    {
        return 'gift';
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('gift.option', GiftOption::class);
        StorefrontHooks::add('cart.summary', fn () => Gift::enabled() ? view('gift::partials.summary') : null, 15);
        StorefrontHooks::add('admin.order.customer', 'gift::partials.admin-order');
        StorefrontHooks::add('admin.order.print', 'gift::partials.print');

        Quoter::extend('gift', fn (QuoteContext $context, Quote $quote) => app(Gift::class)->apply($context, $quote), priority: 25);
        Event::listen('order.placed', fn (array $order) => rescue(fn () => app(Gift::class)->ordered($order), report: true));

        ReportMetrics::add('gift', function (Carbon $from, Carbon $to) {
            $gifts = OrderGift::query()->whereBetween('created_at', [$from, $to]);

            return [['label' => 'سفارش‌های هدیه', 'value' => PersianText::toPersianDigits((clone $gifts)->count()),
                'hint' => 'درآمد بسته‌بندی: '.Money::toman((int) (clone $gifts)->sum('fee')).' تومان']];
        });

        Settings::section(new SettingsSection(
            key: 'gift',
            title: 'بسته‌بندی کادو',
            icon: 'heroicon-o-gift-top',
            sort: 39,
            defaults: ['enabled' => true, 'fee' => 0, 'label' => 'بسته‌بندی کادو'],
            schema: fn () => [
                Toggle::make('enabled')->label('گزینه‌ی «هدیه است؟» در سبد و صفحه‌ی پرداخت'),
                TomanInput::make('fee')->label('هزینه‌ی بسته‌بندی کادو')->helperText('۰ = رایگان'),
                TextInput::make('label')->label('عنوان در فاکتور')->required(),
            ],
        ));
    }
}
