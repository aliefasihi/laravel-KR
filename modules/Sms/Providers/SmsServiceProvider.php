<?php

/**
 * Khaneh Romantic — SMS module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Sms\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Modules\Sms\Support\Sms;

class SmsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(Sms::class);
    }

    public function boot(): void
    {
        parent::boot();

        Settings::section(new SettingsSection(
            key: 'sms',
            title: 'پیامک',
            icon: 'heroicon-o-chat-bubble-left-right',
            sort: 30,
            defaults: (array) config('modules.sms'),
            schema: fn () => [
                Select::make('driver')->label('سرویس پیامک')->options(Sms::DRIVERS)->required()->native(false)->live(),
                Text::make('کدها فقط در storage/logs/laravel.log نوشته می‌شوند؛ برای سایت واقعی یکی از سرویس‌ها را انتخاب کنید.')
                    ->color('warning')->visible(fn (Get $get) => $get('driver') === 'log'),
                Fieldset::make('کاوه‌نگار')->visible(fn (Get $get) => $get('driver') === 'kavenegar')->schema([
                    TextInput::make('kavenegar_key')->label('API Key')->password()->revealable()->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
                    TextInput::make('kavenegar_otp_template')->label('نام الگوی کد ورود')->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText('الگو در پنل کاوه‌نگار، با %token% برای کد. مثال: «کد ورود شما به خانه رمانتیک: %token%»'),
                    TextInput::make('kavenegar_sender')->label('شماره خط (برای پیامک متنی)')->extraInputAttributes(['dir' => 'ltr']),
                ]),
                Fieldset::make('sms.ir')->visible(fn (Get $get) => $get('driver') === 'smsir')->schema([
                    TextInput::make('smsir_key')->label('API Key')->password()->revealable()->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
                    TextInput::make('smsir_otp_template')->label('شناسه‌ی قالب کد ورود')->numeric()
                        ->helperText('قالب «ارسال سریع» در پنل sms.ir'),
                    TextInput::make('smsir_otp_parameter')->label('نام پارامتر کد در قالب')->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText('مثلاً CODE برای #CODE#'),
                    TextInput::make('smsir_line')->label('شماره خط (برای پیامک متنی)')->extraInputAttributes(['dir' => 'ltr']),
                ]),
            ],
        ));
    }
}
