<?php

/**
 * Khaneh Romantic — Admin: attribute library (each attribute with its allowed values)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Filament\Resources\Attributes;

use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Modules\Attributes\Filament\Resources\Attributes\Pages\CreateAttribute;
use Modules\Attributes\Filament\Resources\Attributes\Pages\EditAttribute;
use Modules\Attributes\Filament\Resources\Attributes\Pages\ListAttributes;
use Modules\Attributes\Models\ProductAttribute;
use UnitEnum;

class AttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'ویژگی';

    protected static ?string $pluralModelLabel = 'ویژگی‌ها';

    protected static ?string $slug = 'attributes';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('ویژگی')->schema([
                Grid::make(3)->schema([
                    TextInput::make('name')->label('نام')->required()->maxLength(120)->placeholder('جنس، رنگ، ظرفیت…'),
                    TextInput::make('slug')->label('کلید انگلیسی')->maxLength(64)->unique(ignoreRecord: true)
                        ->extraInputAttributes(['dir' => 'ltr'])->placeholder('material')
                        ->helperText('در آدرس فیلترها می‌آید. خالی بماند، از نام ساخته می‌شود.'),
                    TextInput::make('unit')->label('واحد')->maxLength(32)->placeholder('سانتی‌متر، لیتر'),
                ]),
                Grid::make(4)->schema([
                    Select::make('display')->label('نمایش در فیلتر')->options(ProductAttribute::DISPLAYS)->default('text')->native(false)->live(),
                    Toggle::make('is_filterable')->label('فیلتر در صفحه‌ی دسته')->default(true)->inline(false),
                    Toggle::make('is_visible')->label('در جدول مشخصات')->default(true)->inline(false),
                    TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
                ]),
            ]),
            Section::make('مقادیر مجاز')
                ->description('محصول فقط از همین فهرست مقدار می‌گیرد (متن آزاد ندارد) — همین فیلترها را یکدست نگه می‌دارد. با کشیدن مرتب کنید. تغییر نام یک مقدار در همه‌ی محصولات و مدل‌ها اعمال می‌شود؛ حذفش، آن را از محصولاتی که دارندش هم برمی‌دارد.')
                ->schema([
                    Repeater::make('values')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('sort')
                        ->reorderable()
                        ->schema([
                            TextInput::make('value')->label('مقدار')->required()->maxLength(120)
                                ->distinct(),
                            ColorPicker::make('color')->label('رنگ')
                                ->visible(fn (Get $get) => $get('../../display') === 'color'),
                        ])
                        ->columns(2)
                        ->grid(['md' => 2, 'xl' => 3])
                        ->defaultItems(0)
                        ->addActionLabel('افزودن مقدار')
                        ->itemLabel(fn (array $state) => $state['value'] ?? null)
                        ->collapsible(false),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        $fa = fn ($state) => PersianText::toPersianDigits((string) $state);

        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('نام')->searchable()->weight('bold'),
                TextColumn::make('slug')->label('کلید')->fontFamily(FontFamily::Mono)->color('gray'),
                TextColumn::make('unit')->label('واحد')->placeholder('—'),
                TextColumn::make('values_count')->label('مقادیر')->counts('values')->formatStateUsing($fa),
                IconColumn::make('is_filterable')->label('فیلتر')->boolean(),
                IconColumn::make('is_visible')->label('مشخصات')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_filterable')->label('فیلتر'),
            ])
            ->recordActions([EditAction::make(), self::deleteAction(DeleteAction::make())]);
    }

    /** Deleting an attribute takes its values off every product: say how many before. */
    public static function deleteAction(DeleteAction $action): DeleteAction
    {
        return $action->modalDescription(function (ProductAttribute $record) {
            $products = DB::table('product_attribute_values')->where('attribute_id', $record->id)->distinct()->count('product_id');
            $groups = DB::table('attribute_group_items')->where('attribute_id', $record->id)->count();

            return $products || $groups
                ? 'این ویژگی از '.PersianText::toPersianDigits($groups).' گروه و مقدارهایش از '.PersianText::toPersianDigits($products).' محصول برداشته می‌شود و فیلترش هم از صفحه‌ی دسته‌ها می‌رود. برگشت ندارد.'
                : 'هیچ محصول و گروهی از این ویژگی استفاده نمی‌کند.';
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit' => EditAttribute::route('/{record}/edit'),
        ];
    }
}
