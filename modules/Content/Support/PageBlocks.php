<?php

/**
 * Khaneh Romantic — Blocks of the About / Contact pages: editor fields and starter content
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Each block renders with content::pages.blocks.{type}. Add a block = a Block here + a view.
 */

namespace Modules\Content\Support;

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

        return match ($key) {
            'about' => [
                ['type' => 'hero', 'data' => ['eyebrow' => 'Our Story', 'title' => 'درباره '.$store['name'], 'text' => $store['about'], 'image' => null]],
                ['type' => 'stats', 'data' => ['items' => [
                    ['value' => '۱۰۰٪', 'label' => 'کالای اصل با ضمانت اصالت'],
                    ['value' => 'سراسر ایران', 'label' => 'ارسال با بسته‌بندی ایمن ظروف'],
                    ['value' => 'همراه شما', 'label' => 'مشاوره پیش و پس از خرید'],
                ]]],
            ],
            'contact' => [
                ['type' => 'hero', 'data' => ['eyebrow' => null, 'title' => 'تماس با ما', 'text' => 'برای مشاوره‌ی خرید، پیگیری سفارش یا هر سؤالی، از هر یک از راه‌های زیر با ما در ارتباط باشید.', 'image' => null]],
                ['type' => 'contact_cards', 'data' => ['title' => null, 'show_store' => true, 'items' => []]],
                ['type' => 'address_map', 'data' => ['title' => 'آدرس فروشگاه حضوری', 'address' => $store['address'], 'lat' => null, 'lng' => null, 'map_embed' => null, 'hours' => []]],
                ['type' => 'contact_form', 'data' => ['title' => 'پیام بگذارید', 'text' => 'پیامتان مستقیم به همکاران ما می‌رسد و به‌زودی پاسخ می‌دهیم.', 'subjects' => ContactForm::DEFAULT_SUBJECTS]],
            ],
            default => [],
        };
    }
}
