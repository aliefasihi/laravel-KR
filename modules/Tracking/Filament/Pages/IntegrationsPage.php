<?php

/**
 * Khaneh Romantic — Admin: «اتصال ابزارها» — Search Console, Bing, Analytics, Tag Manager, Clarity, pixels
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Tracking\Filament\Pages;

use App\Core\Settings\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Modules\Tracking\Support\Tracking;
use UnitEnum;

class IntegrationsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'اتصال ابزارها و آمار';

    protected static ?string $title = 'اتصال ابزارها و آمار';

    protected static ?string $slug = 'integrations';

    public const FIELDS = [
        'offline_mode' => false, 'skip_admins' => true, 'delay' => 5,
        'verify_google' => null, 'verify_bing' => null, 'verify_yandex' => null, 'verify_facebook' => null, 'verify_pinterest' => null,
        'ga4' => null, 'gtm' => null, 'clarity' => null, 'meta_pixel' => null, 'custom_head' => null,
    ];

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $data = [];
        foreach (self::FIELDS as $field => $default) {
            $data[$field] = Settings::get('tracking.'.$field, $default);
        }
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $link = fn (string $url, string $label) => '<a href="'.e($url).'" target="_blank" rel="noopener" style="color:#cc3366;font-weight:700">'.e($label).' ↗</a>';
        $code = fn (string $text) => '<code dir="ltr" style="background:#f1f5f9;border-radius:6px;padding:1px 6px">'.e($text).'</code>';

        return $schema->statePath('data')->components([
            Section::make('قطعی اینترنت بین‌الملل')->icon('heroicon-o-globe-alt')->schema([
                Text::make('هیچ‌کدام از ابزارهای پایین سرعت سایت را کم نمی‌کند: کدهای تأیید فقط یک متاتگ‌اند و آمار و پیکسل‌ها بعد از باز شدن کامل صفحه و اولین حرکت بازدیدکننده، جدا و پس‌زمینه‌ای لود می‌شوند. اگر اینترنت بین‌الملل قطع باشد فقط آمار ثبت نمی‌شود؛ صفحه منتظر نمی‌ماند.')->color('gray'),
                Toggle::make('offline_mode')->label('حالت اینترنت ملی: همه‌ی اسکریپت‌های خارجی خاموش')
                    ->helperText('برای روزهای قطعی کامل. کدهای تأیید (سرچ کنسول و…) سر جایشان می‌مانند.'),
            ]),

            Section::make('تأیید مالکیت سایت')->description('کد را از خود سرویس بگیرید و این‌جا بگذارید؛ کل متاتگ را هم کپی کنید قبول است.')
                ->icon('heroicon-o-check-badge')->columns(2)->schema([
                    TextInput::make('verify_google')->label('گوگل سرچ کنسول')->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText(new HtmlString($link('https://search.google.com/search-console', 'سرچ کنسول').' ← افزودن ویژگی ← پیشوند نشانی ← روش «تگ HTML»')),
                    TextInput::make('verify_bing')->label('بینگ وب‌مستر')->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText(new HtmlString($link('https://www.bing.com/webmasters', 'Bing Webmaster').' ← روش «HTML Meta Tag» (یا وارد کردن مستقیم از سرچ کنسول)')),
                    TextInput::make('verify_yandex')->label('یاندکس وب‌مستر')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('verify_facebook')->label('تأیید دامنه‌ی فیسبوک / اینستاگرام')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('verify_pinterest')->label('پینترست')->extraInputAttributes(['dir' => 'ltr']),
                    Text::make(new HtmlString('نقشه‌ی سایت برای ثبت در سرچ کنسول و بینگ: '.$code(url('sitemap.xml')).' — '.$link(url('sitemap.xml'), 'دیدن').'<br>robots.txt: '.$link(url('robots.txt'), url('robots.txt'))))->columnSpanFull(),
                ]),

            Section::make('آمار و پیکسل‌ها')->description('با تأخیر و پس‌زمینه‌ای لود می‌شوند؛ خالی = لود نمی‌شود.')
                ->icon('heroicon-o-chart-bar')->columns(2)->schema([
                    TextInput::make('ga4')->label('Google Analytics 4 — Measurement ID')->placeholder('G-XXXXXXXXXX')->extraInputAttributes(['dir' => 'ltr'])
                        ->rule('nullable')->regex('/^G-[A-Za-z0-9]{4,}$/')
                        ->helperText('اگر آنالیتیکس را از داخل تگ منیجر وصل کرده‌اید، این‌جا خالی بماند تا بازدیدها دو بار شمرده نشود.'),
                    TextInput::make('gtm')->label('Google Tag Manager — Container ID')->placeholder('GTM-XXXXXXX')->extraInputAttributes(['dir' => 'ltr'])
                        ->regex('/^GTM-[A-Za-z0-9]{4,}$/'),
                    TextInput::make('clarity')->label('Microsoft Clarity — Project ID')->placeholder('abcd1234ef')->extraInputAttributes(['dir' => 'ltr'])
                        ->regex('/^[A-Za-z0-9]{6,20}$/')
                        ->helperText(new HtmlString('رایگان: ضبط حرکت موس و نقشه‌ی کلیک بازدیدکننده‌ها. '.$link('https://clarity.microsoft.com', 'clarity.microsoft.com'))),
                    TextInput::make('meta_pixel')->label('Meta (فیسبوک/اینستاگرام) Pixel ID')->placeholder('123456789012345')->extraInputAttributes(['dir' => 'ltr'])
                        ->regex('/^\d{6,20}$/'),
                    TextInput::make('delay')->label('حداکثر تأخیر بارگذاری (ثانیه)')->integer()->minValue(1)->maxValue(20)
                        ->helperText('اگر بازدیدکننده تا این مدت تکان نخورد، آمار باز هم لود می‌شود.'),
                    Toggle::make('skip_admins')->label('بازدید مدیرها شمرده نشود')->inline(false),
                ]),

            Section::make('کد دلخواه')->description('یکتانت، نجوا، گفتینو، هات‌جار و هر سرویس دیگر: کدی که خود سرویس داده را بگذارید.')
                ->icon('heroicon-o-code-bracket')->collapsible()->schema([
                    Textarea::make('custom_head')->hiddenLabel()->rows(8)->extraInputAttributes(['dir' => 'ltr', 'style' => 'font-family:monospace;font-size:12px'])
                        ->helperText('این کد هم مثل آمار با تأخیر و پس‌زمینه‌ای اجرا می‌شود و در حالت اینترنت ملی خاموش است. فقط کد سرویس‌های مطمئن را بگذارید.'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label('ذخیره')->submit('save')->keyBindings(['mod+s'])])]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $values = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $value = $state[$field] ?? null;
            if (str_starts_with($field, 'verify_') && is_string($value)) {
                $value = Tracking::code($value);   // whole <meta> tag pasted → just its code
            }
            $values['tracking.'.$field] = $value;
        }
        Settings::set($values);

        Notification::make()->success()->title('ذخیره شد')->send();
    }
}
