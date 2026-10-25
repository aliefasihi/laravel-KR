<?php

/**
 * Khaneh Romantic — Blocks of the About / Contact pages: editor fields and starter content
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Each block renders with content::pages.blocks.{type}. Add a block = a Block here + a view.
 */

namespace Modules\Content\Support;

use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Modules\Content\Livewire\ContactForm;

final class PageBlocks
{
    public const ICONS = [
        'sparkles' => 'درخشش', 'truck' => 'ارسال', 'shield-check' => 'ضمانت', 'heart' => 'قلب', 'gift' => 'هدیه',
        'star' => 'ستاره', 'chat-bubble-left-right' => 'گفتگو', 'home' => 'خانه', 'clock' => 'ساعت', 'phone' => 'تلفن',
        'map-pin' => 'مکان', 'credit-card' => 'پرداخت', 'arrow-path' => 'مرجوعی', 'check-badge' => 'اصالت',
    ];

    private static function image(string $name = 'image', string $label = 'تصویر'): FileUpload
    {
        return FileUpload::make($name)->label($label)->image()->disk('public')->directory('pages')->maxSize(4096)->imageEditor();
    }

    /** @return list<Block> */
    public static function blocks(): array
    {
        return [
            Block::make('hero')->label('سرآغاز (عنوان بزرگ)')->icon('heroicon-o-sparkles')->schema([
                Grid::make(2)->schema([
                    TextInput::make('eyebrow')->label('برچسب کوچک بالای عنوان')->maxLength(60),
                    TextInput::make('title')->label('عنوان')->required()->maxLength(120),
                ]),
                Textarea::make('text')->label('متن')->rows(3),
                self::image(),
            ]),
            Block::make('text')->label('متن')->icon('heroicon-o-bars-3-bottom-right')->schema([
                TextInput::make('title')->label('عنوان (اختیاری)'),
                RichEditor::make('content')->label('متن')->required(),
            ]),
            Block::make('stats')->label('اعداد و نکته‌های کوتاه')->icon('heroicon-o-chart-bar')->schema([
                Repeater::make('items')->label('موارد')->schema([
                    TextInput::make('value')->label('عدد / عنوان')->required()->maxLength(30),
                    TextInput::make('label')->label('توضیح')->required()->maxLength(80),
                ])->columns(2)->minItems(1)->maxItems(6)->defaultItems(3)->collapsible(),
            ]),
            Block::make('features')->label('ویژگی‌ها با آیکن')->icon('heroicon-o-squares-2x2')->schema([
                TextInput::make('title')->label('عنوان (اختیاری)'),
                Repeater::make('items')->label('موارد')->schema([
                    Select::make('icon')->label('آیکن')->options(self::ICONS)->default('sparkles')->native(false),
                    TextInput::make('title')->label('عنوان')->required(),
                    Textarea::make('text')->label('متن')->rows(2)->columnSpanFull(),
                ])->columns(2)->minItems(1)->maxItems(8)->collapsible(),
            ]),
            Block::make('image_text')->label('تصویر و متن')->icon('heroicon-o-photo')->schema([
                self::image()->required(),
                TextInput::make('title')->label('عنوان'),
                RichEditor::make('content')->label('متن'),
                Select::make('image_side')->label('جای تصویر')->options(['start' => 'سمت راست', 'end' => 'سمت چپ'])->default('start')->native(false),
            ]),
            Block::make('gallery')->label('گالری تصاویر')->icon('heroicon-o-rectangle-group')->schema([
                TextInput::make('title')->label('عنوان (اختیاری)'),
                self::image('images', 'تصاویر')->multiple()->reorderable()->maxFiles(12)->required(),
            ]),
            Block::make('timeline')->label('تاریخچه / مسیر')->icon('heroicon-o-clock')->schema([
                TextInput::make('title')->label('عنوان (اختیاری)'),
                Repeater::make('items')->label('مرحله‌ها')->schema([
                    TextInput::make('year')->label('سال / زمان')->required()->maxLength(20),
                    TextInput::make('title')->label('عنوان')->required(),
                    Textarea::make('text')->label('متن')->rows(2)->columnSpanFull(),
                ])->columns(2)->minItems(1)->collapsible(),
            ]),
            Block::make('faq')->label('پرسش‌های پرتکرار')->icon('heroicon-o-question-mark-circle')->schema([
                TextInput::make('title')->label('عنوان')->default('پرسش‌های پرتکرار'),
                Repeater::make('items')->label('پرسش‌ها')->schema([
                    TextInput::make('question')->label('پرسش')->required(),
                    Textarea::make('answer')->label('پاسخ')->rows(3)->required(),
                ])->minItems(1)->collapsible()->itemLabel(fn (array $state) => $state['question'] ?? null),
            ]),
            Block::make('quote')->label('نقل‌قول')->icon('heroicon-o-chat-bubble-bottom-center-text')->schema([
                Textarea::make('text')->label('متن')->rows(3)->required(),
                TextInput::make('author')->label('گوینده'),
            ]),
            Block::make('cta')->label('دعوت به اقدام (دکمه)')->icon('heroicon-o-cursor-arrow-rays')->schema([
                TextInput::make('title')->label('عنوان')->required(),
                Textarea::make('text')->label('متن')->rows(2),
                Grid::make(2)->schema([
                    TextInput::make('button_label')->label('متن دکمه')->default('مشاهده‌ی فروشگاه'),
                    TextInput::make('button_url')->label('لینک دکمه')->default('/shop')->extraInputAttributes(['dir' => 'ltr']),
                ]),
            ]),
            Block::make('contact_cards')->label('راه‌های تماس')->icon('heroicon-o-phone')->schema([
                TextInput::make('title')->label('عنوان (اختیاری)'),
                Toggle::make('show_store')->label('تلفن، موبایل، واتس‌اپ و ایمیل فروشگاه (از تنظیمات)')->default(true),
                Repeater::make('items')->label('موارد بیشتر')->schema([
                    TextInput::make('label')->label('عنوان')->required(),
                    TextInput::make('value')->label('مقدار')->required(),
                    TextInput::make('url')->label('لینک (tel:… یا https://…)')->extraInputAttributes(['dir' => 'ltr']),
                ])->columns(3)->defaultItems(0)->collapsible(),
            ]),
            Block::make('address_map')->label('نشانی، نقشه و ساعت کاری')->icon('heroicon-o-map-pin')->schema([
                TextInput::make('title')->label('عنوان')->default('آدرس فروشگاه حضوری'),
                Textarea::make('address')->label('نشانی')->rows(2)->default(fn () => config('khaneh.store.address')),
                Grid::make(2)->schema([
                    TextInput::make('lat')->label('عرض جغرافیایی (lat)')->numeric()->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('lng')->label('طول جغرافیایی (lng)')->numeric()->extraInputAttributes(['dir' => 'ltr']),
                ]),
                TextInput::make('map_embed')->label('آدرس نقشه‌ی جاسازی‌شده (src کد iframe گوگل/نشان/بلد) — اختیاری')
                    ->url()->extraInputAttributes(['dir' => 'ltr']),
                Repeater::make('hours')->label('ساعت کاری')->schema([
                    TextInput::make('days')->label('روزها')->required(),
                    TextInput::make('time')->label('ساعت')->required(),
                ])->columns(2)->defaultItems(0)->collapsible(),
            ]),
            Block::make('contact_form')->label('فرم تماس')->icon('heroicon-o-envelope')->schema([
                TextInput::make('title')->label('عنوان')->default('پیام بگذارید'),
                Textarea::make('text')->label('توضیح')->rows(2),
                TagsInput::make('subjects')->label('موضوع‌ها')->default(ContactForm::DEFAULT_SUBJECTS),
            ]),
            Block::make('socials')->label('شبکه‌های اجتماعی')->icon('heroicon-o-share')->schema([
                TextInput::make('title')->label('عنوان')->default('ما را دنبال کنید'),
            ]),
        ];
    }

    /** Starter content: what the pages showed before they became editable. */
    public static function defaults(string $key): array
    {
        $store = config('khaneh.store');
        $name = $store['name'];
        $days = PersianText::toPersianDigits((string) Settings::get('returns.window_days', 7));
        $p = fn (string ...$paragraphs) => implode('', array_map(fn ($t) => '<p>'.$t.'</p>', $paragraphs));
        $list = fn (string ...$items) => '<ul>'.implode('', array_map(fn ($t) => '<li>'.$t.'</li>', $items)).'</ul>';

        return match ($key) {
            'about' => [
                ['type' => 'hero', 'data' => ['eyebrow' => 'Our Story', 'title' => 'بیش از ۲۰ سال همراه خانه‌های شیک و گرم', 'image' => null,
                    'text' => $name.' حاصل بیش از دو دهه تجربه، عشق و تخصص در حوزه‌ی خانه و آشپزخانه است. باور داریم خانه فقط یک فضای فیزیکی نیست؛ جایی است که احساس، آرامش، زیبایی و خاطره در آن جریان دارد.']],
                ['type' => 'stats', 'data' => ['items' => [
                    ['value' => '+۲۰ سال', 'label' => 'تجربه‌ی تخصصی در لوازم خانه و آشپزخانه'],
                    ['value' => '۱۰۰٪', 'label' => 'کالای اصل با ضمانت اصالت'],
                    ['value' => 'سراسر ایران', 'label' => 'ارسال با بسته‌بندی ایمن ظروف'],
                ]]],
                ['type' => 'text', 'data' => ['title' => 'داستان ما', 'content' => $p(
                    'فعالیت ما از حدود ۲۰ سال پیش و به‌صورت تخصصی در بازار لوازم خانه و آشپزخانه آغاز شد. در این سال‌ها از نزدیک با سلیقه‌ها، نیازها و دغدغه‌های مشتریان آشنا شدیم و یاد گرفتیم که انتخاب یک ظرف، یک اکسسوری دکوری یا حتی کوچک‌ترین وسیله‌ی خانه، می‌تواند تأثیر بزرگی بر حس‌وحال فضا بگذارد. این تجربه‌ی ارزشمند امروز پایه‌ی برند '.$name.' است.',
                    $name.' جایی است برای کسانی که به جزئیات اهمیت می‌دهند؛ کسانی که دوست دارند آشپزخانه‌شان فقط محل پخت‌وپز نباشد، بلکه فضایی دلنشین و الهام‌بخش باشد. مجموعه‌ای متنوع از محصولات خانه و آشپزخانه، سرو و پذیرایی، دکوراسیون، اکسسوری‌های خاص و لوازم کاربردی را گرد هم آورده‌ایم تا هر خانه‌ای امضای خاص خود را داشته باشد.',
                    'در '.$name.' تلاش کرده‌ایم پلی میان سلیقه‌ی کلاسیک و سبک‌های مدرن بسازیم. چه طرفدار طرح‌های ظریف و کلاسیک باشید، چه عاشق خطوط ساده و مدرن، محصولی متناسب با سبک زندگی شما این‌جا هست.',
                )]],
                ['type' => 'features', 'data' => ['title' => 'چرا '.$name.'؟', 'items' => [
                    ['icon' => 'check-badge', 'title' => 'انتخاب با وسواس', 'text' => 'هر محصول پیش از ورود به مجموعه از نظر طراحی، کیفیت ساخت، کاربرد و هماهنگی با سبک‌های شیک بررسی می‌شود.'],
                    ['icon' => 'shield-check', 'title' => 'زیبایی در کنار دوام', 'text' => 'زیبایی زمانی ارزشمند است که کنار دوام و کیفیت بنشیند؛ کالاها اصل و با ضمانت اصالت عرضه می‌شوند.'],
                    ['icon' => 'chat-bubble-left-right', 'title' => 'مشاوره پیش و پس از خرید', 'text' => 'از انتخاب محصول تا پس از تحویل، همکاران ما با حوصله کنار شما هستند.'],
                    ['icon' => 'truck', 'title' => 'بسته‌بندی ایمن و ارسال سراسری', 'text' => 'ظروف و اکسسوری‌ها با بسته‌بندی ویژه‌ی کالای شکستنی به سراسر ایران فرستاده می‌شوند.'],
                ]]],
                ['type' => 'quote', 'data' => ['text' => 'اعتماد مشتریان بزرگ‌ترین سرمایه‌ی ماست؛ به شفافیت، صداقت و احترام به شما پایبندیم.', 'author' => $name]],
                ['type' => 'cta', 'data' => ['title' => 'خانه‌ای شیک، خاص و رمانتیک', 'text' => 'اگر شما هم به دنبال خانه‌ای زیباتر و گرم‌تر هستید، '.$name.' با افتخار کنار شماست.', 'button_label' => 'دیدن محصولات', 'button_url' => '/shop']],
            ],
            'contact' => [
                ['type' => 'hero', 'data' => ['eyebrow' => 'Contact', 'title' => 'با ما در ارتباط باشید', 'image' => null,
                    'text' => 'پرسشی درباره‌ی محصولات دارید، برای انتخاب به راهنمایی نیاز دارید یا پیشنهادی برای بهتر شدن خدمات ما؟ با کمال میل پاسخگوی شما هستیم.']],
                ['type' => 'contact_cards', 'data' => ['title' => null, 'show_store' => true, 'items' => []]],
                ['type' => 'address_map', 'data' => ['title' => 'فروشگاه حضوری', 'address' => $store['address'], 'lat' => null, 'lng' => null, 'map_embed' => null, 'hours' => []]],
                ['type' => 'contact_form', 'data' => ['title' => 'پیام بگذارید', 'text' => 'پیامتان مستقیم به همکاران ما می‌رسد و در کوتاه‌ترین زمان پاسخ می‌دهیم.', 'subjects' => ContactForm::DEFAULT_SUBJECTS]],
            ],
            'privacy' => [
                ['type' => 'hero', 'data' => ['eyebrow' => 'Privacy', 'title' => 'حریم خصوصی شما', 'image' => null,
                    'text' => 'حفظ حریم خصوصی مشتریان در '.$name.' یک اصل غیرقابل چشم‌پوشی است. این صفحه می‌گوید چه اطلاعاتی از شما نگه می‌داریم، برای چه و چطور از آن محافظت می‌کنیم.']],
                ['type' => 'text', 'data' => ['title' => 'چه اطلاعاتی نگه می‌داریم', 'content' => $p('فقط اطلاعاتی را می‌گیریم که برای خرید و ارسال لازم است:')
                    .$list('نام و شماره‌ی موبایل، برای ورود با کد پیامکی و حساب کاربری', 'نشانی و کد پستی، برای ارسال سفارش', 'سفارش‌ها و پرداخت‌ها، برای پیگیری، فاکتور و پشتیبانی', 'پیام‌هایی که از فرم تماس، نظرها یا پرسش‌ها می‌فرستید')
                    .$p('شماره‌ی کارت و رمز بانکی شما هرگز در سایت ما ثبت نمی‌شود؛ پرداخت در صفحه‌ی امن درگاه بانکی انجام می‌شود.')]],
                ['type' => 'text', 'data' => ['title' => 'از اطلاعات شما چطور استفاده می‌کنیم', 'content' => $list(
                    'ثبت، ارسال و پیگیری سفارش و فرستادن پیامک وضعیت سفارش',
                    'پاسخ به پیام‌ها و درخواست‌های پشتیبانی و مرجوعی',
                    'باشگاه مشتریان، کد تخفیف و پیشنهادهای ویژه؛ فقط اگر خودتان عضو شده باشید',
                ).$p('اطلاعات شخصی شما به هیچ عنوان فروخته نمی‌شود و در اختیار اشخاص ثالث قرار نمی‌گیرد؛ جز شرکت ارسال (فقط نام، تلفن و نشانی برای تحویل) و مراجع قانونی در صورت درخواست رسمی.')]],
                ['type' => 'text', 'data' => ['title' => 'امنیت و کوکی‌ها', 'content' => $p(
                    'ارتباط با سایت رمزنگاری‌شده (HTTPS) است و دسترسی به اطلاعات مشتریان فقط برای همکارانی است که برای انجام سفارش به آن نیاز دارند.',
                    'سایت از کوکی برای نگه داشتن سبد خرید و ورود شما استفاده می‌کند. ابزارهای آمار، اطلاعات کلی و بی‌نام بازدیدها را می‌سنجند تا سایت را بهتر کنیم.',
                )]],
                ['type' => 'text', 'data' => ['title' => 'حقوق شما', 'content' => $list(
                    'اطلاعات حساب و نشانی‌هایتان را هر زمان از «حساب کاربری» ببینید و ویرایش کنید',
                    'هر زمان از خبرنامه و پیامک‌های تبلیغاتی خارج شوید',
                    'برای حذف حساب و اطلاعاتتان از راه‌های «تماس با ما» درخواست بدهید',
                )]],
                ['type' => 'cta', 'data' => ['title' => 'پرسشی دارید؟', 'text' => 'درباره‌ی اطلاعات خودتان هر سؤالی دارید، با ما در میان بگذارید.', 'button_label' => 'تماس با ما', 'button_url' => '/contact-us']],
            ],
            'returns' => [
                ['type' => 'hero', 'data' => ['eyebrow' => 'Returns', 'title' => 'شرایط مرجوعی و بازگشت کالا', 'image' => null,
                    'text' => 'می‌خواهیم با خیال راحت خرید کنید. اگر کالایی مطابق انتظارتان نبود، تا '.$days.' روز پس از تحویل می‌توانید درخواست مرجوعی ثبت کنید.']],
                ['type' => 'stats', 'data' => ['items' => [
                    ['value' => $days.' روز', 'label' => 'مهلت ثبت درخواست پس از تحویل'],
                    ['value' => 'از حساب کاربری', 'label' => 'ثبت و پیگیری آنلاین درخواست'],
                    ['value' => 'کارت یا کیف پول', 'label' => 'بازگشت وجه به انتخاب شما'],
                ]]],
                ['type' => 'text', 'data' => ['title' => 'شرایط پذیرش', 'content' => $list(
                    'کالا استفاده نشده باشد و در بسته‌بندی اصلی، همراه همه‌ی متعلقات و فاکتور باشد',
                    'درخواست حداکثر '.$days.' روز پس از تحویل سفارش ثبت شده باشد',
                    'آسیب‌دیدگی در ارسال، کالای اشتباه یا معیوب را با عکس در همان روز تحویل گزارش کنید تا سریع‌تر رسیدگی شود',
                )]],
                ['type' => 'timeline', 'data' => ['title' => 'مراحل مرجوعی', 'items' => [
                    ['year' => '۱', 'title' => 'ثبت درخواست', 'text' => 'از «حساب کاربری ← سفارش‌های من»، کالا و دلیل را انتخاب کنید و در صورت نیاز عکس بفرستید.'],
                    ['year' => '۲', 'title' => 'بررسی و تأیید', 'text' => 'درخواست بررسی می‌شود و نتیجه و نشانی ارسال با پیامک به شما اعلام می‌شود.'],
                    ['year' => '۳', 'title' => 'ارسال کالا', 'text' => 'کالا را با بسته‌بندی مناسب برای ما بفرستید.'],
                    ['year' => '۴', 'title' => 'بازگشت وجه', 'text' => 'پس از دریافت و بررسی کالا، مبلغ به کارت (شماره‌ی شبا) یا کیف پول شما برگردانده می‌شود.'],
                ]]],
                ['type' => 'faq', 'data' => ['title' => 'پرسش‌های پرتکرار', 'items' => [
                    ['question' => 'هزینه‌ی ارسال مرجوعی با کیست؟', 'answer' => 'اگر کالا معیوب، آسیب‌دیده یا اشتباه ارسال شده باشد، هزینه با ما است. در بقیه‌ی موارد هزینه‌ی ارسال برگشت با مشتری است.'],
                    ['question' => 'وجه چه زمانی برمی‌گردد؟', 'answer' => 'پس از رسیدن کالا و تأیید شرایط آن. اگر بازگشت به کیف پول را انتخاب کنید، همان لحظه شارژ می‌شود.'],
                    ['question' => 'می‌توانم کالا را تعویض کنم؟', 'answer' => 'بله؛ در توضیح درخواست مرجوعی بنویسید که تعویض می‌خواهید، یا با ما تماس بگیرید.'],
                ]]],
                ['type' => 'cta', 'data' => ['title' => 'نیاز به راهنمایی دارید؟', 'text' => 'پیش از ثبت درخواست، می‌توانید با همکاران ما مشورت کنید.', 'button_label' => 'تماس با ما', 'button_url' => '/contact-us']],
            ],
            default => [],
        };
    }
}
