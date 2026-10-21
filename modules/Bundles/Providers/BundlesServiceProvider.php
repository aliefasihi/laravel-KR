<?php

/**
 * Khaneh Romantic — Sets with a discount and «often bought together»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bundles\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Livewire\Livewire;
use Modules\Bundles\Livewire\ProductOffers;
use Modules\Bundles\Support\Bundles;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Checkout\Support\Pricing\Quoter;

class BundlesServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Livewire::component('bundles.product-offers', ProductOffers::class);
        StorefrontHooks::add('product.sections', 'bundles::partials.section', 5);   // above the reviews

        // set discounts come before coupons
        Quoter::extend('bundles', fn (QuoteContext $context, Quote $quote) => app(Bundles::class)->apply($context, $quote), priority: 5);

        Settings::section(new SettingsSection(
            key: 'bundles',
            title: 'خرید با هم',
            icon: 'heroicon-o-squares-plus',
            sort: 38,
            defaults: ['together' => true, 'together_min' => 2],
            schema: fn () => [
                Toggle::make('together')->label('«معمولاً با هم خریده می‌شوند» در صفحه‌ی محصول نمایش داده شود'),
                TextInput::make('together_min')->label('حداقل تعداد سفارش مشترک')->numeric()->minValue(1)->required()
                    ->helperText('دو محصول وقتی پیشنهاد می‌شوند که دست‌کم این تعداد سفارش هر دو را با هم داشته باشد. ست‌های تخفیف‌دار را در «بازاریابی ← ست‌های تخفیف‌دار» بسازید.'),
            ],
        ));
    }
}
