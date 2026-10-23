<?php

/**
 * Khaneh Romantic — Wholesale (فروش عمده / همکاران) module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Providers;

use App\Core\Modules\Modules;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Checkout\Support\CartLine;
use Modules\Checkout\Support\Pricing\LinePrices;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Checkout\Support\Pricing\Quoter;
use Modules\Crm\Support\FormDefinition;
use Modules\Crm\Support\Forms;
use Modules\Wholesale\Livewire\ApplicationForm;
use Modules\Wholesale\Livewire\InquiryForm;
use Modules\Wholesale\Livewire\PriceList;
use Modules\Wholesale\Support\Wholesale;

class WholesaleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(Wholesale::class);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('wholesale.apply', ApplicationForm::class);
        Livewire::component('wholesale.price-list', PriceList::class);
        Livewire::component('wholesale.inquiry', InquiryForm::class);

        // the wholesale customer's cart lines get the wholesale unit price
        LinePrices::extend('wholesale', fn ($lines) => app(Wholesale::class)->linePrices($lines));

        // wholesale prices don't stack with coupons and set discounts (unless allowed)
        Quoter::extend('wholesale', function (QuoteContext $context, Quote $quote) {
            $wholesale = $context->lines->contains(fn (CartLine $line) => $line->priceLabel === Wholesale::LABEL);
            if ($wholesale) {
                $quote->meta['wholesale'] = true;
                if (! Settings::get('wholesale.stack', false)) {
                    $quote->meta['no_discounts'] = 'روی قیمت همکار، کد تخفیف و تخفیف ست اعمال نمی‌شود.';
                }
            }
        }, priority: 1);

        Event::listen('order.placing', function ($order, Quote $quote) {
            if (! empty($quote->meta['wholesale'])) {
                $order->note('سفارش همکار — با قیمت عمده.', 'system');
            }
        });

        StorefrontHooks::add('product.buybox', 'wholesale::partials.product', 15);
        StorefrontHooks::add('cart.summary', 'wholesale::partials.cart', 5);
        StorefrontHooks::add('admin.order.customer', 'wholesale::filament.admin-order');

        if (class_exists(Forms::class) && Modules::enabled('Crm')) {
            Forms::define(new FormDefinition('wholesale_inquiry', 'استعلام خرید عمده', [
                'name' => 'نام', 'mobile' => 'موبایل', 'company' => 'شرکت / فروشگاه', 'city' => 'شهر', 'items' => 'کالاها و تعداد', 'budget' => 'بودجه', 'date' => 'زمان لازم',
            ], icon: 'heroicon-o-building-storefront', sort: 32));
        }

        Settings::section(new SettingsSection(
            key: 'wholesale',
            title: 'فروش عمده',
            icon: 'heroicon-o-building-storefront',
            sort: 38,
            defaults: [
                'tiers' => [['min_qty' => 1, 'percent' => 10], ['min_qty' => 6, 'percent' => 13], ['min_qty' => 12, 'percent' => 15]],
                'min_order' => 0, 'rounding' => 1000, 'stack' => false, 'auto_approve' => false, 'teaser' => true,
                'intro' => null, 'terms' => null, 'approved_template' => null, 'rejected_template' => null,
            ],
            schema: fn () => [
                Repeater::make('tiers')->label('پله‌های پیش‌فرض قیمت همکار')
                    ->helperText('برای همه‌ی محصولات، مگر دسته، برند یا محصولی که در «قوانین قیمت عمده» قانون جدا دارد. تعداد = از یک مدل در سبد.')
                    ->schema([
                        TextInput::make('min_qty')->label('از تعداد')->integer()->minValue(1)->required()->suffix('عدد'),
                        TextInput::make('percent')->label('درصد زیر قیمت اصلی')->numeric()->minValue(0)->maxValue(90)->required()->suffix('٪'),
                    ])->columns(2)->defaultItems(1)->addActionLabel('پله‌ی جدید')->reorderable(false),
                Fieldset::make('شرایط')->columns(3)->schema([
                    TextInput::make('min_order')->label('حداقل خرید برای قیمت همکار')->integer()->minValue(0)->suffix('تومان')
                        ->helperText('جمع سبد با قیمت عادی. ۰ = بدون حداقل.'),
                    Select::make('rounding')->label('رُند کردن قیمت همکار')->native(false)
                        ->options([0 => 'بدون رُند', 100 => '۱۰۰ تومان', 1000 => '۱٬۰۰۰ تومان', 10000 => '۱۰٬۰۰۰ تومان']),
                    Toggle::make('stack')->label('کد تخفیف و تخفیف ست هم روی قیمت همکار اعمال شود')->inline(false),
                    Toggle::make('auto_approve')->label('درخواست همکاری بدون بررسی تأیید شود')->inline(false),
                    Toggle::make('teaser')->label('«همکار هستید؟» زیر دکمه‌ی خرید برای بقیه')->inline(false),
                ]),
                Textarea::make('intro')->label('معرفی همکاری (بالای صفحه‌ی /wholesale)')->rows(3)
                    ->placeholder('مثلاً: مغازه‌دار یا فروشنده‌ی اینترنتی هستید؟ با ثبت درخواست همکاری، همه‌ی محصولات را با قیمت عمده بخرید.'),
                Textarea::make('terms')->label('شرایط همکاری')->rows(4)
                    ->placeholder('مثلاً: حداقل خرید هر سفارش ۵ میلیون تومان · تسویه پیش از ارسال · ارسال با باربری به هزینه‌ی همکار'),
                Fieldset::make('پیامک‌ها')->columns(2)->schema([
                    Text::make('مقادیر: SHOP (نام فروشگاه؛ token) · NOTE (دلیل رد؛ token10)')->color('gray')->columnSpanFull(),
                    TextInput::make('approved_template')->label('همکاری تأیید شد')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('rejected_template')->label('درخواست رد شد')->extraInputAttributes(['dir' => 'ltr']),
                ]),
            ],
        ));
    }
}
