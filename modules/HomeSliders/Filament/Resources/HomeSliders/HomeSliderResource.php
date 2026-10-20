<?php

/**
 * Khaneh Romantic — Admin: home page product sliders
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\HomeSliders\Filament\Resources\HomeSliders;

use App\Core\Support\StoreCache;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\HomeSliders\Filament\Resources\HomeSliders\Pages\CreateHomeSlider;
use Modules\HomeSliders\Filament\Resources\HomeSliders\Pages\EditHomeSlider;
use Modules\HomeSliders\Filament\Resources\HomeSliders\Pages\ListHomeSliders;
use Modules\HomeSliders\Models\HomeSlider;
use UnitEnum;

class HomeSliderResource extends Resource
{
    protected static ?string $model = HomeSlider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'اسلایدر محصولات';

    protected static ?string $pluralModelLabel = 'اسلایدرهای صفحه اصلی';

    protected static ?string $slug = 'home-sliders';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Group::make([
                Section::make('عنوان')
                    ->description('عنوان دو رنگ است: «کلمه‌ی رنگی» + «بقیه‌ی عنوان». مثال: جدیدترین + محصولات')
                    ->schema([
                        TextInput::make('accent')->label('کلمه‌ی رنگی')->maxLength(40)->placeholder('جدیدترین'),
                        TextInput::make('title')->label('بقیه‌ی عنوان')->required()->maxLength(80)->placeholder('محصولات'),
                        TextInput::make('ghost')->label('کلمه‌ی لاتین کم‌رنگ پشت عنوان')->maxLength(40)->placeholder('New Arrivals')
                            ->extraInputAttributes(['dir' => 'ltr'])->helperText('اختیاری؛ فقط در طرح ساده'),
                    ])->columns(3),
                Section::make('کدام محصولات؟')->schema([
                    Select::make('source')->label('منبع')->options(HomeSlider::SOURCES)->default('category')->required()->native(false)->live(),
                    Select::make('category_id')->label('دسته‌بندی')->relationship('category', 'name')->searchable()->preload()
                        ->helperText('زیردسته‌ها هم حساب می‌شوند.')
                        ->visible(fn (Get $get) => $get('source') === 'category')->required(fn (Get $get) => $get('source') === 'category'),
                    Select::make('brand_id')->label('برند')->relationship('brand', 'name_fa')->searchable()->preload()
                        ->visible(fn (Get $get) => $get('source') === 'brand')->required(fn (Get $get) => $get('source') === 'brand'),
                    Select::make('sort_by')->label('ترتیب')->options(HomeSlider::SORTS)->default('newest')->required()->native(false),
                    TextInput::make('limit')->label('تعداد محصول')->numeric()->minValue(4)->maxValue(40)->default(16)->required(),
                    Toggle::make('only_in_stock')->label('فقط کالاهای موجود')->default(true),
                    Toggle::make('only_sale')->label('فقط کالاهای تخفیف‌دار')
                        ->visible(fn (Get $get) => $get('source') !== 'sale'),
                ])->columns(2),
            ])->columnSpan(['lg' => 2]),
            Group::make([
                Section::make('نمایش')->schema([
                    Toggle::make('is_active')->label('فعال')->default(true),
                    Select::make('slot')->label('جای اسلایدر در صفحه‌ی اصلی')->options(HomeSlider::SLOTS)->default('after_categories')->required()->native(false)
                        ->helperText('اسلایدرهای یک جا به ترتیب جدول (با کشیدن) نمایش داده می‌شوند.'),
                    Radio::make('style')->label('طرح')->options(HomeSlider::STYLES)->default('glass')->required(),
                    Toggle::make('autoplay')->label('حرکت خودکار')->default(true)->live(),
                    TextInput::make('interval')->label('هر چند ثانیه یک کارت جلو برود')->numeric()->minValue(2)->maxValue(15)->default(4)
                        ->suffix('ثانیه')->visible(fn (Get $get) => $get('autoplay')),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->afterReordering(fn () => StoreCache::flush()) // plain UPDATE, no model events
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->label('عنوان')
                    ->formatStateUsing(fn (HomeSlider $record) => trim($record->accent.' '.$record->title))
                    ->description(fn (HomeSlider $record) => match ($record->source) {
                        'category' => 'دسته: '.($record->category?->name ?? '—'),
                        'brand' => 'برند: '.($record->brand?->name_fa ?? '—'),
                        default => HomeSlider::SOURCES[$record->source] ?? $record->source,
                    }),
                TextColumn::make('slot')->label('جا')->labels(HomeSlider::SLOTS)->badge()->color('gray'),
                TextColumn::make('style')->label('طرح')->formatStateUsing(fn (string $state) => $state === 'band' ? 'نوار رنگی' : 'ساده'),
                TextColumn::make('limit')->label('تعداد')->numeric(),
                ToggleColumn::make('is_active')->label('فعال'),
            ])
            ->filters([
                SelectFilter::make('slot')->label('جا')->options(HomeSlider::SLOTS),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('کپی')->excludeAttributes(['sort'])
                    ->mutateRecordDataUsing(fn (array $data) => ['title' => $data['title'].' (کپی)', 'is_active' => false] + $data),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('هنوز اسلایدری نساخته‌اید')
            ->emptyStateDescription('برای هر دسته یا برند یک اسلایدر بسازید تا در صفحه‌ی اصلی نمایش داده شود.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeSliders::route('/'),
            'create' => CreateHomeSlider::route('/create'),
            'edit' => EditHomeSlider::route('/{record}/edit'),
        ];
    }
}
