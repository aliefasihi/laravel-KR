<?php

/**
 * Khaneh Romantic — Returns (مرجوعی) module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Livewire\Livewire;
use Modules\Returns\Livewire\ReturnForm;

class ReturnsServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Livewire::component('returns.form', ReturnForm::class);
        StorefrontHooks::add('account.order.actions', 'returns::partials.order-actions');

        Settings::section(new SettingsSection(
            key: 'returns',
            title: 'مرجوعی',
            icon: 'heroicon-o-arrow-uturn-left',
            sort: 39,
            defaults: ['window_days' => 7, 'photo_required' => true, 'policy' => null, 'instructions' => null,
                'approved_template' => null, 'rejected_template' => null, 'refunded_template' => null],
            schema: fn () => [
                TextInput::make('window_days')->label('مهلت مرجوعی بعد از تحویل')->integer()->minValue(1)->maxValue(60)->suffix('روز'),
                Toggle::make('photo_required')->label('برای «آسیب‌دیده»، «اشتباه»، «ایراد فنی» و «متفاوت با سایت» عکس لازم باشد'),
                Textarea::make('policy')->label('شرایط مرجوعی (بالای فرم برای مشتری)')->rows(3)
                    ->placeholder('مثلاً: کالا باید سالم و در بسته‌بندی اصلی باشد. هزینه‌ی ارسال کالای سالمی که مشتری منصرف شده با خود مشتری است.'),
                Textarea::make('instructions')->label('راهنمای ارسال پیش‌فرض (هنگام تأیید قابل تغییر است)')->rows(3),
                Fieldset::make('پیامک‌ها')->columns(3)->schema([
                    Text::make('مقادیر: RETURN (شماره مرجوعی) · ORDER (شماره سفارش) · AMOUNT (مبلغ برگشتی) · NOTE (راهنما یا دلیل رد؛ token10)')->color('gray')->columnSpanFull(),
                    TextInput::make('approved_template')->label('تأیید شد')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('rejected_template')->label('رد شد')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('refunded_template')->label('وجه برگشت داده شد')->extraInputAttributes(['dir' => 'ltr']),
                ]),
            ],
        ));
    }
}
