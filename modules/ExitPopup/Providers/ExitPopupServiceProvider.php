<?php

/**
 * Khaneh Romantic — Exit popup (پاپ‌آپ خروج) module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\ExitPopup\Providers;

use App\Core\Modules\Modules;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\PersianText;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Modules\Coupon\Models\Coupon;
use Modules\ExitPopup\Support\ExitPopup;

class ExitPopupServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'exit';
    }

    public function register(): void
    {
        parent::register();

        $this->app->scoped(ExitPopup::class);
    }

    public function boot(): void
    {
        parent::boot();

        StorefrontHooks::add('body.end', fn () => app(ExitPopup::class)->eligible(request()->route()?->getName())
            ? view('exit::partials.popup')
            : '', 90);

        Settings::section(new SettingsSection(
            key: 'exit_popup',
            title: 'پاپ‌آپ خروج',
            icon: 'heroicon-o-arrow-right-start-on-rectangle',
            sort: 37,
            defaults: [
                'enabled' => false, 'offer' => 'coupon', 'coupon_id' => null, 'personal_days' => 3, 'sms_template' => null,
                'title' => null, 'text' => null, 'button_label' => null, 'button_url' => null,
                'cart_first' => true, 'delay' => 8, 'days' => 3, 'mobile' => true,
                'pages' => array_keys(ExitPopup::PAGES), 'audience' => 'no_orders',
            ],
            schema: fn () => [
                Text::make(fn () => self::statsLine())->color('gray'),
                Toggle::make('enabled')->label('پاپ‌آپ خروج روشن باشد'),
                Fieldset::make('پیشنهاد')->columns(2)->schema([
                    Radio::make('offer')->label('چه چیزی نشان داده شود؟')->options(ExitPopup::OFFERS)->live()->columnSpanFull(),
                    Select::make('coupon_id')->label(fn (Get $get) => $get('offer') === 'personal' ? 'کد تخفیف الگو' : 'کد تخفیف')
                        ->visible(fn (Get $get) => $get('offer') !== 'message' && Modules::enabled('Coupon'))
                        ->options(fn () => Coupon::query()->whereNull('allowed_mobiles')->latest('id')->limit(200)->get()
                            ->mapWithKeys(fn (Coupon $c) => [$c->id => $c->code.' — '.$c->summary().($c->is_active ? '' : ' (غیرفعال)')])->all())
                        ->searchable()->native(false)
                        ->helperText(fn (Get $get) => $get('offer') === 'personal'
                            ? 'شرایط (درصد، سقف، حداقل خرید، دسته‌ها…) از این کد کپی می‌شود. بهتر است خود این کد «غیرفعال» باشد تا فقط کدهای شخصی کار کنند.'
                            : 'کدی که در «بازاریابی ← کدهای تخفیف» ساخته‌اید.'),
                    TextInput::make('personal_days')->label('اعتبار کد شخصی')->integer()->minValue(1)->maxValue(60)->suffix('روز')
                        ->visible(fn (Get $get) => $get('offer') === 'personal'),
                    TextInput::make('sms_template')->label('الگوی پیامک کد شخصی')->extraInputAttributes(['dir' => 'ltr'])
                        ->visible(fn (Get $get) => $get('offer') === 'personal')
                        ->helperText('مقادیر: CODE (کد؛ token) · OFFER (مقدار تخفیف؛ token2) · DAYS (روز اعتبار؛ token3)'),
                    TextInput::make('title')->label('عنوان')->placeholder('صبر کنید؛ یک هدیه برایتان داریم!')->maxLength(80),
                    TextInput::make('text')->label('متن')->placeholder('با این کد، خرید امروزتان تخفیف می‌خورد.')->maxLength(200),
                    TextInput::make('button_label')->label('متن دکمه')->placeholder('دیدن پیشنهادها')->maxLength(40)
                        ->visible(fn (Get $get) => $get('offer') === 'message'),
                    TextInput::make('button_url')->label('لینک دکمه')->placeholder('/shop?sale=1')->extraInputAttributes(['dir' => 'ltr'])
                        ->visible(fn (Get $get) => $get('offer') === 'message'),
                ]),
                Fieldset::make('کی و برای چه کسی')->columns(3)->schema([
                    TextInput::make('delay')->label('بعد از چند ثانیه در صفحه')->integer()->minValue(0)->maxValue(120)->suffix('ثانیه')
                        ->helperText('قبلش با خروج هم نشان داده نمی‌شود.'),
                    TextInput::make('days')->label('هر بازدیدکننده هر چند روز یک بار')->integer()->minValue(1)->maxValue(90)->suffix('روز'),
                    Select::make('audience')->label('برای')->options(ExitPopup::AUDIENCES)->native(false),
                    Toggle::make('cart_first')->label('اگر سبد خرید پر است، اول یادآوری سبد')->inline(false),
                    Toggle::make('mobile')->label('در موبایل هم (با اسکرول سریع رو به بالا)')->inline(false),
                    CheckboxList::make('pages')->label('در این صفحه‌ها')->options(ExitPopup::PAGES)->columns(3)->columnSpanFull()
                        ->helperText('سبد خرید، پرداخت و حساب کاربری هیچ‌وقت.'),
                ]),
            ],
        ));
    }

    private static function statsLine(): string
    {
        $s = ExitPopup::stats(30);
        $rate = $s['shown'] ? round($s['converted'] * 100 / $s['shown'], 1) : 0;

        return '۳۰ روز اخیر: '.PersianText::toPersianDigits($s['shown']).' بار نمایش، '
            .PersianText::toPersianDigits($s['converted']).' استفاده ('.PersianText::toPersianDigits((string) $rate).'٪)، '
            .PersianText::toPersianDigits($s['leads']).' شماره‌ی جدید';
    }
}
