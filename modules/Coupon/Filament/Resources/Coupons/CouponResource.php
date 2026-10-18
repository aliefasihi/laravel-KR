<?php

/**
 * Khaneh Romantic — Admin: coupons
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Filament\Resources\Coupons;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use App\Filament\Forms\TomanInput;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Coupon\Filament\Resources\Coupons\Pages\CreateCoupon;
use Modules\Coupon\Filament\Resources\Coupons\Pages\EditCoupon;
use Modules\Coupon\Filament\Resources\Coupons\Pages\ListCoupons;
use Modules\Coupon\Models\Coupon;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'کد تخفیف';

    protected static ?string $pluralModelLabel = 'کدهای تخفیف';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        $products = fn (string $name, string $label) => Select::make($name)->label($label)->multiple()->searchable()
            ->getSearchResultsUsing(fn (string $search) => Product::query()->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                ->limit(30)->pluck('name', 'id')->all())
            ->getOptionLabelsUsing(fn (array $values) => Product::query()->whereIn('id', $values)->pluck('name', 'id')->all());
        $categories = fn (string $name, string $label) => Select::make($name)->label($label)->multiple()->searchable()->preload()
            ->options(fn () => Category::query()->orderBy('path')->pluck('name', 'id')->all());

        return $schema->components([
            Group::make([
                Section::make('کد و مقدار')->schema([
                    TextInput::make('code')->label('کد')->required()->maxLength(40)->unique(ignoreRecord: true)
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'text-transform:uppercase'])
                        ->suffixAction(Action::make('generate')->icon('heroicon-m-sparkles')->tooltip('ساخت کد تصادفی')
                            ->action(fn (Set $set) => $set('code', Coupon::generateCode()))),
                    TextInput::make('description')->label('توضیح داخلی')->placeholder('مثلاً: کمپین یلدا اینستاگرام'),
                    Radio::make('type')->label('نوع')->options(Coupon::TYPES)->default('percent')->inline()->live()->required(),
                    TextInput::make('amount')->label(fn (Get $get) => $get('type') === 'percent' ? 'درصد تخفیف' : 'مبلغ تخفیف (تومان)')
                        ->numeric()->minValue(0)->maxValue(fn (Get $get) => $get('type') === 'percent' ? 100 : null)->default(10)->required()
                        ->suffix(fn (Get $get) => $get('type') === 'percent' ? '٪' : 'تومان')
                        ->formatStateUsing(fn ($state, Get $get) => $get('type') === 'fixed' && $state ? intdiv((int) $state, 10) : $state)
                        ->dehydrateStateUsing(fn ($state, Get $get) => $get('type') === 'fixed' ? (int) $state * 10 : (int) $state),
                    TomanInput::make('max_discount')->label('سقف تخفیف')->visible(fn (Get $get) => $get('type') === 'percent'),
                    Toggle::make('free_shipping')->label('ارسال رایگان هم بدهد')->inline(false),
                ])->columns(2),
                Section::make('محدودیت محصولات')
                    ->description('خالی = همه‌ی محصولات. اگر محصول، دسته یا برند انتخاب شود، فقط کالاهای منطبق تخفیف می‌گیرند.')
                    ->collapsible()
                    ->schema([
                        $products('product_ids', 'فقط این محصولات'),
                        $products('exclude_product_ids', 'به‌جز این محصولات'),
                        $categories('category_ids', 'فقط این دسته‌ها (با زیردسته‌ها)'),
                        $categories('exclude_category_ids', 'به‌جز این دسته‌ها'),
                        Select::make('brand_ids')->label('فقط این برندها')->multiple()->preload()
                            ->options(fn () => Brand::query()->orderBy('name_fa')->pluck('name_fa', 'id')->all()),
                        Toggle::make('exclude_sale_items')->label('کالاهای حراج شامل نشوند')->inline(false),
                    ])->columns(2),
            ])->columnSpan(['lg' => 2]),
            Group::make([
                Section::make('وضعیت')->schema([
                    Toggle::make('is_active')->label('فعال')->default(true),
                    DateTimePicker::make('starts_at')->label('شروع'),
                    DateTimePicker::make('ends_at')->label('پایان')->after('starts_at'),
                ]),
                Section::make('شرایط استفاده')->schema([
                    TomanInput::make('min_subtotal')->label('حداقل مبلغ سبد'),
                    TomanInput::make('max_subtotal')->label('حداکثر مبلغ سبد'),
                    TextInput::make('usage_limit')->label('کل دفعات استفاده')->numeric()->minValue(1)->placeholder('نامحدود'),
                    TextInput::make('usage_limit_per_customer')->label('دفعات برای هر مشتری')->numeric()->minValue(1)->placeholder('نامحدود'),
                    Toggle::make('first_order_only')->label('فقط اولین خرید'),
                    TagsInput::make('allowed_mobiles')->label('فقط این شماره‌ها')->placeholder('09121234567')
                        ->helperText('برای کد شخصی؛ خالی = همه'),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')->label('کد')->searchable()->copyable()->weight('bold')->fontFamily('mono')
                    ->description(fn (Coupon $record) => $record->description),
                TextColumn::make('summary')->label('تخفیف')->state(fn (Coupon $record) => $record->summary()),
                TextColumn::make('used_count')->label('استفاده')->sortable()
                    ->formatStateUsing(fn (Coupon $record) => PersianText::toPersianDigits($record->used_count).($record->usage_limit ? ' از '.PersianText::toPersianDigits($record->usage_limit) : '')),
                TextColumn::make('ends_at')->label('پایان')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Jalali::format($state) : null)->placeholder('بدون پایان')
                    ->color(fn (Coupon $record) => $record->ends_at?->isPast() ? 'danger' : null),
                ToggleColumn::make('is_active')->label('فعال'),
            ])
            ->filters([TernaryFilter::make('is_active')->label('فعال')])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('کپی')->excludeAttributes(['used_count'])
                    ->mutateRecordDataUsing(fn (array $data) => ['code' => Coupon::generateCode()] + $data),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
