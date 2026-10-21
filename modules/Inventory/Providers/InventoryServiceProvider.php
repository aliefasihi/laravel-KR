<?php

/**
 * Khaneh Romantic — Inventory module: low-stock list and alerts (panel bell + SMS to the shop)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Inventory\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\ProductVariant;
use Modules\Inventory\Support\LowStock;

class InventoryServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // stock moves with orders (plain UPDATEs, no model events) and with edits in the panel
        foreach (['order.placed', 'order.status_changed', 'order.refunded'] as $event) {
            Event::listen($event, function () {
                LowStock::later();
            });
        }
        ProductVariant::saved(function (ProductVariant $variant) {
            if ($variant->wasChanged(['stock', 'is_active']) || $variant->wasRecentlyCreated) {
                LowStock::later();
            }
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(fn () => app(LowStock::class)->scan())->everyThirtyMinutes()->name('inventory:low-stock')->withoutOverlapping();
        });

        Settings::section(new SettingsSection(
            key: 'inventory',
            title: 'موجودی انبار',
            icon: 'heroicon-o-archive-box',
            sort: 38,
            defaults: ['threshold' => 3, 'bell' => true, 'mobiles' => null, 'sms_template' => null],
            schema: fn () => [
                TextInput::make('threshold')->label('حد هشدار موجودی')->integer()->minValue(0)->maxValue(1000)->suffix('عدد و کمتر')
                    ->helperText('مدلی که موجودی‌اش به این عدد برسد در «کم‌موجودها» می‌آید و یک بار خبر داده می‌شود؛ بعد از شارژ دوباره حساب می‌شود.'),
                Toggle::make('bell')->label('اعلان در زنگوله‌ی پنل مدیریت (برای همه‌ی مدیرها)'),
                Fieldset::make('پیامک به فروشگاه')->columns(2)->schema([
                    Text::make('مقادیر الگو به ترتیب: STOCK (موجودی فعلی) · MORE (تعداد مدل‌های دیگری که هم‌زمان کم شدند) · LINK (لینک فهرست در پنل) · PRODUCT (نام کالا؛ در کاوه‌نگار token10). شب‌ها (۲۱ تا ۹) پیامک نمی‌رود و صبح فرستاده می‌شود.')->color('gray')->columnSpanFull(),
                    TextInput::make('mobiles')->label('شماره‌ها')->placeholder('خالی = همان شماره‌های «پیامک سفارش تازه»')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('sms_template')->label('الگو')->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText('نمونه: موجودی «%token10%» به %token% رسید (+%token2% مدل دیگر). %token3%'),
                ]),
            ],
        ));
    }
}
