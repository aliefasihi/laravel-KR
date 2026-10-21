<?php

/**
 * Khaneh Romantic — Payment module: Zarinpal, SnappPay instalments, card-to-card, cash on delivery
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Registers the methods the checkout offers (Order module's PaymentMethods), the gateway callback,
 * the receipt form on the order page and a «پرداخت» tab in the store settings.
 */

namespace Modules\Payment\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use App\Filament\Forms\TomanInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Livewire;
use Modules\Order\Payments\PaymentMethods;
use Modules\Payment\Livewire\ReceiptUpload;
use Modules\Payment\Methods\CardToCardMethod;
use Modules\Payment\Methods\CashOnDeliveryMethod;
use Modules\Payment\Methods\SnappPayMethod;
use Modules\Payment\Methods\ZarinpalMethod;
use Modules\Payment\Support\PaymentReview;

class PaymentServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(PaymentReview::class);
    }

    public function boot(): void
    {
        parent::boot();

        PaymentMethods::register(new ZarinpalMethod, 10);
        PaymentMethods::register(new CardToCardMethod, 20);
        PaymentMethods::register(new CashOnDeliveryMethod, 30);
        PaymentMethods::register(new SnappPayMethod, 15);

        // «or 4 instalments of …» under the price (every product page design)
        StorefrontHooks::add('product.buybox', fn (array $data) => SnappPayMethod::enabled() && Settings::get('payment.snapppay_badge', true)
            ? view('payment::partials.installments', $data) : null, 5);

        Livewire::component('payment.receipt-upload', ReceiptUpload::class);
        StorefrontHooks::add('order.payment', 'payment::partials.order-payment');

        $provinces = config('modules.customer.provinces') ?: [];

        Settings::section(new SettingsSection(
            key: 'payment',
            title: 'پرداخت',
            icon: 'heroicon-o-credit-card',
            sort: 20,
            defaults: [
                'zarinpal_enabled' => filled(config('modules.payment.zarinpal_merchant')),
                'zarinpal_merchant' => config('modules.payment.zarinpal_merchant'),
                'zarinpal_sandbox' => (bool) config('modules.payment.zarinpal_sandbox'),
                'card_enabled' => false,
                'cod_enabled' => false,
                'cod_provinces' => ['تهران'],
            ],
            schema: fn () => [
                Fieldset::make('زرین‌پال')->schema([
                    Toggle::make('zarinpal_enabled')->label('فعال')->live(),
                    Toggle::make('zarinpal_sandbox')->label('حالت آزمایشی (sandbox)'),
                    TextInput::make('zarinpal_merchant')->label('مرچنت کد')->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull()
                        ->required(fn (Get $get) => $get('zarinpal_enabled')),
                    TextInput::make('zarinpal_title')->label('عنوان در صفحه‌ی پرداخت')->placeholder('پرداخت آنلاین (همه‌ی کارت‌های بانکی)')->columnSpanFull(),
                ])->columns(2),
                Fieldset::make('خرید اقساطی — اسنپ‌پی')->schema([
                    Toggle::make('snapppay_enabled')->label('فعال')->live(),
                    Toggle::make('snapppay_badge')->label('«۴ قسط …» زیر قیمت محصول نمایش داده شود')->default(true),
                    TextInput::make('snapppay_base_url')->label('آدرس سرویس (Base URL)')->url()->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull()
                        ->required(fn (Get $get) => $get('snapppay_enabled'))->helperText('آدرس و چهار مقدار زیر را اسنپ‌پی بعد از عقد قرارداد پذیرندگی می‌دهد.'),
                    TextInput::make('snapppay_client_id')->label('Client ID')->extraInputAttributes(['dir' => 'ltr'])->required(fn (Get $get) => $get('snapppay_enabled')),
                    TextInput::make('snapppay_client_secret')->label('Client Secret')->password()->revealable()->extraInputAttributes(['dir' => 'ltr'])->required(fn (Get $get) => $get('snapppay_enabled')),
                    TextInput::make('snapppay_username')->label('نام کاربری')->extraInputAttributes(['dir' => 'ltr'])->required(fn (Get $get) => $get('snapppay_enabled')),
                    TextInput::make('snapppay_password')->label('رمز')->password()->revealable()->extraInputAttributes(['dir' => 'ltr'])->required(fn (Get $get) => $get('snapppay_enabled')),
                    TomanInput::make('snapppay_min')->label('حداقل مبلغ سفارش'),
                    TomanInput::make('snapppay_max')->label('حداکثر مبلغ سفارش')->helperText('خالی = بدون سقف (اسنپ‌پی خودش هم سقف اعتبار را بررسی می‌کند)'),
                    TextInput::make('snapppay_title')->label('عنوان در صفحه‌ی پرداخت')->placeholder('خرید اقساطی با اسنپ‌پی (۴ قسط)')->columnSpanFull(),
                ])->columns(2),
                Fieldset::make('کارت به کارت')->schema([
                    Toggle::make('card_enabled')->label('فعال')->live()->columnSpanFull(),
                    TextInput::make('card_number')->label('شماره کارت')->extraInputAttributes(['dir' => 'ltr'])->required(fn (Get $get) => $get('card_enabled')),
                    TextInput::make('card_holder')->label('به نام'),
                    TextInput::make('card_bank')->label('بانک'),
                    TextInput::make('card_sheba')->label('شماره شبا')->extraInputAttributes(['dir' => 'ltr'])->placeholder('IR…'),
                ])->columns(2),
                Fieldset::make('پرداخت در محل')->schema([
                    Toggle::make('cod_enabled')->label('فعال')->columnSpanFull(),
                    Select::make('cod_provinces')->label('فقط این استان‌ها')->multiple()->options(array_combine($provinces, $provinces))
                        ->helperText('خالی = همه‌ی استان‌ها'),
                    TomanInput::make('cod_max')->label('حداکثر مبلغ سفارش'),
                ])->columns(2),
            ],
        ));
    }
}
