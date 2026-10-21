<?php

/**
 * Khaneh Romantic — PWA module: install the shop on the phone's home screen like an app
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Pwa\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Illuminate\Support\Facades\Route;

class PwaServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        StorefrontHooks::add('head.end', 'pwa::head');
        StorefrontHooks::add('body.end', 'pwa::install');

        Settings::section(new SettingsSection(
            key: 'pwa',
            title: 'اپلیکیشن (PWA)',
            icon: 'heroicon-o-device-phone-mobile',
            sort: 60,
            defaults: ['enabled' => true, 'name' => null, 'short_name' => null, 'icon' => null, 'install_banner' => true],
            schema: fn () => [
                Text::make('مشتری‌ها سایت را مثل یک اپ روی صفحه‌ی اصلی گوشی نصب می‌کنند (اندروید، آیفون و ویندوز) — بدون استور. صفحه‌ها همیشه زنده از سرور می‌آیند؛ فقط فایل‌ها و عکس‌ها برای سرعت در گوشی نگه داشته می‌شوند و بدون اینترنت یک صفحه‌ی «اتصال برقرار نیست» نشان داده می‌شود. خریدهای از داخل اپ در گزارش فروش با کانال «اپلیکیشن نصب‌شده» می‌آیند.')->color('gray'),
                Toggle::make('enabled')->label('اپلیکیشن فعال باشد')
                    ->helperText('خاموش کردن، اپ و حافظه‌اش را از مرورگر بازدیدکننده‌ها هم پاک می‌کند (در بازدید بعدی).'),
                TextInput::make('name')->label('نام اپ')->placeholder(config('khaneh.store.name'))->maxLength(45),
                TextInput::make('short_name')->label('نام کوتاه (زیر آیکون)')->placeholder(config('khaneh.store.name'))->maxLength(15)
                    ->helperText('حداکثر ۱۲ تا ۱۵ حرف تا زیر آیکون کامل دیده شود.'),
                FileUpload::make('icon')->label('آیکون اپ')->image()->disk('public')->directory('branding')->visibility('public')
                    ->maxSize(2048)->imagePreviewHeight('96')
                    ->helperText('PNG مربعی حداقل ۵۱۲×۵۱۲. اندازه‌های لازم خودکار ساخته می‌شوند. خالی: آیکون پیش‌فرض خانه.'),
                Toggle::make('install_banner')->label('پیشنهاد نصب به بازدیدکننده‌ها (از بازدید سوم، هر ۳۰ روز یک بار)'),
            ],
        ));
    }

    protected function registerRoutes(): void
    {
        parent::registerRoutes();

        Route::group([], $this->modulePath('routes/pwa.php'));
    }
}
