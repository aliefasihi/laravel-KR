<?php

/**
 * Khaneh Romantic — «تنظیمات عمومی»: name, logo, favicon, contact details, top bar, font
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Everything the storefront and the panel read from config('khaneh.store'), config('khaneh.promo')
 * and config('khaneh.font') can be changed here; saved values are laid over the config at boot,
 * so every view keeps reading the config as before. Empty fields fall back to config/khaneh.php.
 */

namespace App\Core\Settings;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Support\Facades\Storage;

final class SiteSettings
{
    /** fields copied into config('khaneh.store') */
    private const STORE_FIELDS = ['name', 'name_en', 'tagline', 'about', 'phone', 'mobile', 'email', 'address', 'warranty'];

    /** may be cleared (e.g. no Telegram) */
    private const OPTIONAL_STORE_FIELDS = ['whatsapp', 'instagram', 'telegram', 'enamad_url'];

    public static function register(): void
    {
        $store = config('khaneh.store');

        Settings::section(new SettingsSection(
            key: 'site',
            title: 'عمومی',
            icon: 'heroicon-o-building-storefront',
            sort: 1,
            defaults: [
                ...array_intersect_key($store, array_flip([...self::STORE_FIELDS, ...self::OPTIONAL_STORE_FIELDS])),
                'promo_enabled' => filled(config('khaneh.promo')),
                'promo' => config('khaneh.promo') ?: '۱۰٪ تخفیف برای اولین سفارش',
                'font' => config('khaneh.font', 'vazirmatn'),
                'home_title' => 'لوازم آشپزخانه، سرو و پذیرایی و اکسسوری دکوراتیو',
                'home_description' => 'فروشگاه اینترنتی خانه رمانتیک؛ مرجع تخصصی لوازم و اکسسوری خانه و آشپزخانه با ضمانت اصالت کالا و ارسال به سراسر ایران.',
            ],
            schema: fn () => [
                Fieldset::make('نام و معرفی')->columns(2)->schema([
                    TextInput::make('name')->label('نام فروشگاه')->required()->maxLength(60),
                    TextInput::make('name_en')->label('نام انگلیسی')->maxLength(60)->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('tagline')->label('شعار')->maxLength(120)->columnSpanFull()
                        ->helperText('در عنوان صفحه‌هایی که عنوان خودشان را ندارند هم می‌آید.'),
                    Textarea::make('about')->label('درباره‌ی فروشگاه (فوتر)')->rows(3)->columnSpanFull(),
                ]),
                Fieldset::make('لوگو و آیکون')->columns(3)->schema([
                    self::image('logo', 'لوگو', 'PNG/WebP/SVG با پس‌زمینه‌ی شفاف، حدود ۳۲۰×۹۶. خالی: لوگوی نوشتاری.'),
                    self::image('logo_light', 'لوگوی روشن', 'برای پس‌زمینه‌های تیره و حالت شب (اختیاری).'),
                    self::image('favicon', 'آیکون تب مرورگر (favicon)', 'مربعی، PNG حداقل ۱۹۲×۱۹۲.')
                        ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/webp']),
                ]),
                Fieldset::make('صفحه‌ی اصلی در گوگل')->schema([
                    TextInput::make('home_title')->label('عنوان')->maxLength(70)->columnSpanFull(),
                    Textarea::make('home_description')->label('توضیحات')->rows(2)->maxLength(160)->columnSpanFull(),
                ]),
                Fieldset::make('تماس و شبکه‌های اجتماعی')->columns(2)->schema([
                    TextInput::make('phone')->label('تلفن')->tel()->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('mobile')->label('موبایل')->tel()->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('email')->label('ایمیل')->email()->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('whatsapp')->label('واتس‌اپ')->extraInputAttributes(['dir' => 'ltr'])->helperText('بین‌المللی و بدون +، مثل 989121234567'),
                    TextInput::make('instagram')->label('اینستاگرام (آدرس کامل)')->url()->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('telegram')->label('تلگرام (آدرس کامل)')->url()->extraInputAttributes(['dir' => 'ltr']),
                    Textarea::make('address')->label('نشانی')->rows(2)->columnSpanFull(),
                    TextInput::make('enamad_url')->label('لینک نماد اعتماد')->url()->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
                    TextInput::make('warranty')->label('متن ضمانت')->maxLength(60),
                ]),
                Fieldset::make('نوار بالای سایت و فونت')->columns(2)->schema([
                    Toggle::make('promo_enabled')->label('نوار اعلان بالای سایت')->columnSpanFull(),
                    TextInput::make('promo')->label('متن نوار')->maxLength(120)->columnSpanFull(),
                    Radio::make('font')->label('فونت سایت')->options([
                        'vazirmatn' => 'وزیرمتن (رایگان، همراه پروژه)',
                        'kalameh' => 'کلمه (لایسنس‌دار؛ فایل‌ها در public/fonts/kalameh)',
                    ]),
                ]),
            ],
        ));
    }

    private static function image(string $name, string $label, string $help): FileUpload
    {
        return FileUpload::make($name)->label($label)->helperText($help)
            ->image()->disk('public')->directory('branding')->visibility('public')
            ->maxSize(2048)->imagePreviewHeight('80');
    }

    /** config/khaneh.php as it was before any saved values were laid over it */
    private static ?array $original = null;

    /** Lay the saved values over config('khaneh.*') — at boot, and again right after saving. */
    public static function apply(): void
    {
        self::$original ??= ['store' => config('khaneh.store'), 'promo' => config('khaneh.promo'), 'font' => config('khaneh.font')];
        $saved = app(Settings::class)->all();
        $value = fn (string $field) => $saved['site.'.$field] ?? null;

        $store = self::$original['store'];
        foreach (self::STORE_FIELDS as $field) {
            if (filled($value($field))) {
                $store[$field] = $value($field);
            }
        }
        foreach (self::OPTIONAL_STORE_FIELDS as $field) {
            if (array_key_exists('site.'.$field, $saved)) {
                $store[$field] = $value($field) ?: null;
            }
        }

        config([
            'khaneh.store' => $store,
            'khaneh.promo' => array_key_exists('site.promo_enabled', $saved)
                ? ($value('promo_enabled') ? (string) $value('promo') : '')
                : self::$original['promo'],
            'khaneh.font' => $value('font') ?: self::$original['font'],
        ]);
    }

    /** Uploaded logo (or the light one for dark backgrounds), null = the text wordmark. */
    public static function logoUrl(bool $light = false): ?string
    {
        $path = Settings::get($light ? 'site.logo_light' : 'site.logo');

        return is_string($path) && $path !== '' ? Storage::disk('public')->url($path) : null;
    }

    public static function faviconUrl(): string
    {
        $path = Settings::get('site.favicon');

        return is_string($path) && $path !== '' ? Storage::disk('public')->url($path) : '/favicon.ico';
    }
}
