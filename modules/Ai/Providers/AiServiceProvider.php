<?php

/**
 * Khaneh Romantic — AI module: settings and the shared client
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Ai\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\PersianText;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Modules\Ai\Support\Ai;

class AiServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(Ai::class);
    }

    public function boot(): void
    {
        parent::boot();

        Settings::section(new SettingsSection(
            key: 'ai',
            title: 'هوش مصنوعی',
            icon: 'heroicon-o-sparkles',
            sort: 60,
            defaults: [
                'enabled' => false, 'base_url' => 'https://api.openai.com/v1', 'api_key' => null,
                'chat_model' => 'gpt-4o-mini', 'stt_model' => 'whisper-1', 'vision' => true, 'timeout' => 45, 'proxy' => null,
            ],
            schema: fn () => [
                Text::make(function () {
                    $u = Ai::usage();

                    return '۳۰ روز اخیر: '.PersianText::toPersianDigits($u['calls']).' درخواست، '.PersianText::toPersianDigits($u['failed']).' ناموفق، '
                        .PersianText::toPersianDigits(number_format($u['tokens'])).' توکن، میانگین '.PersianText::toPersianDigits(number_format($u['avg_ms'] / 1000, 1)).' ثانیه.';
                })->color('gray'),
                Toggle::make('enabled')->label('هوش مصنوعی روشن باشد')
                    ->helperText('ربات (ویس به متن، پر کردن خودکار محصول) و چت‌بات سایت از آن استفاده می‌کنند. خاموش یا قطع = همان روش قدم‌به‌قدم.'),
                Fieldset::make('سرویس')->columns(2)->schema([
                    TextInput::make('base_url')->label('آدرس API (سازگار با OpenAI)')->extraInputAttributes(['dir' => 'ltr'])->url()
                        ->helperText('OpenAI: https://api.openai.com/v1 · AvalAI: https://api.avalai.ir/v1 · GapGPT: https://api.gapgpt.app/v1'),
                    TextInput::make('api_key')->label('کلید API')->password()->revealable()->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('chat_model')->label('مدل متن و عکس')->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText('مثلاً gpt-4o-mini (ارزان و سریع، عکس هم می‌فهمد).'),
                    TextInput::make('stt_model')->label('مدل تبدیل صوت به متن')->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText('مثلاً whisper-1 یا gpt-4o-mini-transcribe'),
                    Toggle::make('vision')->label('عکس‌ها هم برای هوش مصنوعی فرستاده شوند (رنگ، جنس، توضیحات)')->inline(false),
                    TextInput::make('timeout')->label('حداکثر انتظار')->integer()->minValue(10)->maxValue(120)->suffix('ثانیه'),
                    TextInput::make('proxy')->label('پراکسی (اختیاری)')->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull()
                        ->placeholder('http://user:pass@1.2.3.4:8080  یا  socks5h://1.2.3.4:1080')
                        ->helperText('فقط اگر سرور مستقیم به سرویس خارجی دسترسی ندارد. برای واسطه‌های ایرانی لازم نیست.'),
                ]),
                Actions::make([
                    Action::make('aiTest')->label('آزمایش اتصال (بعد از ذخیره)')->icon('heroicon-o-signal')->color('gray')
                        ->action(function () {
                            $result = app(Ai::class)->test();
                            $result['ok']
                                ? Notification::make()->success()->title('وصل است — جواب: «'.$result['answer'].'» در '.PersianText::toPersianDigits(number_format($result['ms'] / 1000, 1)).' ثانیه')->send()
                                : Notification::make()->danger()->title('وصل نشد')->body(Ai::enabled() ? 'آدرس، کلید و مدل را بررسی کنید؛ جزئیات در لاگ.' : 'اول روشن کنید، کلید بگذارید و ذخیره کنید.')->send();
                        }),
                ]),
            ],
        ));
    }
}
