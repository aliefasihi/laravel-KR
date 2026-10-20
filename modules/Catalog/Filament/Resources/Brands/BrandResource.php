<?php

/**
 * Khaneh Romantic — Admin: brands
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Filament\Resources\Brands;

use App\Filament\Support\Fields;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\Brands\Pages\ManageBrands;
use Modules\Catalog\Models\Brand;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'برند';

    protected static ?string $pluralModelLabel = 'برندها';

    protected static ?string $recordTitleAttribute = 'name_fa';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_fa')->label('نام فارسی')->required()->maxLength(255),
            TextInput::make('name_en')->label('نام انگلیسی')->required()->maxLength(255),
            TextInput::make('slug')
                ->label('اسلاگ (انگلیسی)')
                ->required()
                ->maxLength(64)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->unique(ignoreRecord: true)
                ->helperText('بخش اول آدرس همه‌ی محصولات این برند: /p/{slug}-{sku}. تغییرش آدرس محصولات را عوض می‌کند و آدرس‌های قبلی خودکار ۳۰۱ می‌شوند.'),
            Fields::media()->label('لوگوی برند')
                ->helperText('در بخش برندهای صفحه‌ی اصلی و صفحه‌ی برند نمایش داده می‌شود؛ PNG یا WebP با پس‌زمینه‌ی شفاف.')
                ->columnSpanFull(),
            RichEditor::make('description')->label('معرفی برند')->columnSpanFull(),
            Fields::seo()->collapsed()->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->columns([
                ImageColumn::make('thumb')->label('')->circular()
                    ->state(fn (Brand $record) => $record->media->first()?->absoluteUrlFor(160)),
                TextColumn::make('name_fa')->label('نام')->searchable()->sortable(),
                TextColumn::make('name_en')->label('English')->searchable(),
                TextColumn::make('slug')->label('اسلاگ')->fontFamily(FontFamily::Mono),
                TextColumn::make('products_count')->label('محصولات')->counts('products')->sortable(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBrands::route('/')];
    }
}
