<?php

/**
 * Khaneh Romantic — Wishlist module: saved products, «back in stock» and «price dropped» SMS
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wishlist\Providers;

use App\Core\Modules\Modules;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\PersianText;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\Catalog\Models\ProductVariant;
use Modules\Order\Support\ReportMetrics;
use Modules\Wishlist\Livewire\ProductActions;
use Modules\Wishlist\Models\StockAlert;
use Modules\Wishlist\Models\WishlistItem;
use Modules\Wishlist\Support\Watchers;

class WishlistServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Livewire::component('wishlist.product-actions', ProductActions::class);
        StorefrontHooks::add('product.buybox', 'wishlist::partials.buybox', 20);

        // price or stock changed → check that product's waiting customers right after the response
        ProductVariant::saved(function (ProductVariant $variant) {
            if ($variant->wasChanged(['price', 'sale_price', 'stock', 'is_active', 'sale_starts_at', 'sale_ends_at']) || $variant->wasRecentlyCreated) {
                $productId = (int) $variant->product_id;
                dispatch(fn () => app(Watchers::class)->check($productId))->afterResponse();
            }
        });

        if (class_exists(ReportMetrics::class) && Modules::enabled('Order')) {
            ReportMetrics::add('wishlist', fn (Carbon $from, Carbon $to) => [
                ['label' => 'افزوده به علاقه‌مندی‌ها', 'value' => PersianText::toPersianDigits(WishlistItem::query()->whereBetween('created_at', [$from, $to])->count()),
                    'hint' => PersianText::toPersianDigits(WishlistItem::query()->whereBetween('price_notified_at', [$from, $to])->count()).' پیامک «قیمت کم شد»'],
                ['label' => 'درخواست «موجود شد خبرم کن»', 'value' => PersianText::toPersianDigits(StockAlert::query()->whereBetween('created_at', [$from, $to])->count()),
                    'hint' => PersianText::toPersianDigits(StockAlert::query()->whereBetween('notified_at', [$from, $to])->count()).' پیامک «موجود شد» رفت'],
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(fn () => app(Watchers::class)->sweep())->hourly()->name('wishlist:sweep')->withoutOverlapping();
        });

        Settings::section(new SettingsSection(
            key: 'wishlist',
            title: 'علاقه‌مندی‌ها',
            icon: 'heroicon-o-heart',
            sort: 36,
            defaults: ['back_in_stock_template' => null, 'price_drop_template' => null, 'quiet_hours' => true],
            schema: fn () => [
                Text::make('نام الگو (کاوه‌نگار) یا شناسه‌ی قالب (sms.ir). مقادیر به ترتیب: PRICE (قیمت فعلی، تومان) · OLD (قیمت قبلی) · LINK (لینک محصول) · PRODUCT (نام محصول؛ در کاوه‌نگار token10). خالی = پیامک نمی‌رود و درخواست‌ها می‌مانند.')->color('gray'),
                TextInput::make('back_in_stock_template')->label('الگوی «موجود شد»')->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('نمونه متن الگو: «%token10%» موجود شد! قیمت: %token% تومان — %token3%'),
                TextInput::make('price_drop_template')->label('الگوی «قیمت کم شد» (برای علاقه‌مندی‌ها)')->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('نمونه: قیمت «%token10%» از %token2% به %token% تومان رسید. %token3%'),
                Toggle::make('quiet_hours')->label('شب‌ها پیامک نرود (۲۱ تا ۹ صبح؛ صبح فرستاده می‌شود)'),
            ],
        ));
    }
}
