<?php

/**
 * Khaneh Romantic — Admin: products (URL /p/{brand}-{sku} is derived, never typed)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Filament\Resources\Products;

use App\Core\Modules\Modules;
use App\Core\Support\Money;
use App\Filament\Support\Fields;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Attributes\Filament\Forms\ProductAttributesSection;
use Modules\Catalog\Filament\Resources\Products\Pages\CreateProduct;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Resources\Products\Pages\ListProducts;
use Modules\Catalog\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use Modules\Catalog\Models\Product;
use Modules\FileManager\Filament\Forms\ProductImages;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'محصول';

    protected static ?string $pluralModelLabel = 'محصولات';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Group::make([
                Section::make('اطلاعات محصول')->schema([
                    TextInput::make('name')->label('نام محصول')->required()->maxLength(255),
                    Grid::make(2)->schema([
                        Select::make('brand_id')
                            ->label('برند')
                            ->relationship('brand', 'name_fa')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('sku')
                            ->label('کد کالا (SKU)')
                            ->required(fn (Get $get) => $get('status') === Product::STATUS_ACTIVE)
                            ->validationMessages(['required' => 'بدون کد کالا (SKU) محصول منتشر نمی‌شود.'])
                            ->maxLength(64)
                            ->regex('/[A-Za-z0-9]/')
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->extraInputAttributes(['dir' => 'ltr'])
                            ->helperText('آدرس محصول از برند + SKU ساخته می‌شود. SKU باید حرف یا عدد انگلیسی داشته باشد. تا SKU وارد نشود، محصول فقط پیش‌نویس می‌ماند.'),
                    ]),
                    Textarea::make('short_description')->label('خلاصه (کنار قیمت)')->rows(3),
                    RichEditor::make('description')->label('معرفی کامل'),
                ]),
                ...(Modules::enabled('Attributes') ? [ProductAttributesSection::make()] : []),
                Fields::seo(),
            ])->columnSpan(['lg' => 2]),

            Group::make([
                Section::make('انتشار')->schema([
                    Select::make('status')->label('وضعیت')->options(Product::STATUSES)->default(Product::STATUS_DRAFT)->required()->native(false)->live(),
                    TextInput::make('slug')
                        ->label('آدرس')
                        ->prefix('/p/')
                        ->placeholder('با واردکردن SKU ساخته می‌شود')
                        ->disabled()
                        ->dehydrated(false)
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->visibleOn('edit'),
                ]),
                Section::make('دسته‌بندی')->schema([
                    Select::make('categories')
                        ->hiddenLabel()
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->live(), // «مشخصات فنی» fields follow the category template
                ]),
                Section::make('تصاویر')->schema([
                    // File manager on: the product's own folder opens in a picker (upload, multi-select in order).
                    Modules::enabled('FileManager')
                        ? ProductImages::make('media_ids')->hiddenLabel()
                        : Fields::media(max: null)->hiddenLabel()
                            ->helperText('اول از «کتابخانه تصاویر» آپلود کن. اولین تصویر، تصویر اصلی است.'),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('thumb')->label('')->square()
                    ->state(fn (Product $record) => $record->media->first()?->absoluteUrlFor(160)),
                TextColumn::make('name')->label('نام')->searchable()->sortable()->limit(50)->wrap(),
                TextColumn::make('brand.name_fa')->label('برند')->sortable(),
                TextColumn::make('sku')->label('SKU')->searchable()->copyable()->fontFamily(FontFamily::Mono),
                TextColumn::make('variants_min_price')
                    ->label('از قیمت')
                    ->min('variants', 'price')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : Money::toman((int) $state).' تومان')
                    ->sortable(),
                TextColumn::make('variants_sum_stock')->label('موجودی')->sum('variants', 'stock')->sortable()
                    ->color(fn ($state) => (int) $state < 1 ? 'danger' : null),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->labels(Product::STATUSES)
                    ->color(fn (string $state) => match ($state) {
                        Product::STATUS_ACTIVE => 'success',
                        Product::STATUS_DRAFT => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('updated_at')->label('ویرایش')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(Product::STATUSES),
                SelectFilter::make('brand')->label('برند')->relationship('brand', 'name_fa'),
                SelectFilter::make('categories')->label('دسته‌بندی')->relationship('categories', 'name')->searchable()->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')->label('مشاهده')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Product $record) => $record->url())->openUrlInNewTab()
                    ->visible(fn (Product $record) => $record->status === Product::STATUS_ACTIVE),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->label('انتشار')->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn (Collection $records) => $records->each->update(['status' => Product::STATUS_ACTIVE]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('draft')->label('برگرداندن به پیش‌نویس')->icon(Heroicon::OutlinedPauseCircle)
                        ->action(fn (Collection $records) => $records->each->update(['status' => Product::STATUS_DRAFT]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [VariantsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku'];
    }
}
