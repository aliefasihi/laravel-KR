<?php

/**
 * Khaneh Romantic — Admin: product sets with a discount
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bundles\Filament\Resources\Bundles;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Bundles\Filament\Resources\Bundles\Pages\CreateBundle;
use Modules\Bundles\Filament\Resources\Bundles\Pages\EditBundle;
use Modules\Bundles\Filament\Resources\Bundles\Pages\ListBundles;
use Modules\Bundles\Models\Bundle;
use Modules\Bundles\Support\Bundles;
use Modules\Catalog\Models\Product;
use UnitEnum;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'ست';

    protected static ?string $pluralModelLabel = 'ست‌های تخفیف‌دار';

    protected static ?string $slug = 'bundles';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('ست')->columns(2)->columnSpanFull()->schema([
                TextInput::make('title')->label('عنوان')->required()->maxLength(120)->placeholder('مثلاً: ست آشپزی گلدکیش'),
                TextInput::make('discount_percent')->label('تخفیف ست (درصد)')->numeric()->minValue(0)->maxValue(50)->default(10)->required()
                    ->helperText('وقتی همه‌ی محصولات ست در سبد باشند، روی همین محصولات اعمال می‌شود.'),
                TextInput::make('description')->label('توضیح کوتاه (اختیاری)')->maxLength(200)->columnSpanFull(),
                Toggle::make('is_active')->label('فعال')->default(true),
                TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
            ]),
            Section::make('محصولات ست')->columnSpanFull()->schema([
                Repeater::make('items')->relationship()->hiddenLabel()->orderColumn('sort')->minItems(2)->defaultItems(2)
                    ->addActionLabel('افزودن محصول')->columns(4)
                    ->schema([
                        Select::make('product_id')->label('محصول')->required()->searchable()->columnSpan(3)
                            ->getSearchResultsUsing(fn (string $search) => Product::query()->active()
                                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                                ->limit(30)->pluck('name', 'id')->all())
                            ->getOptionLabelUsing(fn ($value) => Product::query()->find($value)?->name),
                        TextInput::make('quantity')->label('تعداد')->numeric()->minValue(1)->default(1)->required(),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('items.product.variants'))
            ->columns([
                TextColumn::make('title')->label('ست')->searchable()
                    ->description(fn (Bundle $b) => $b->items->map(fn ($i) => ($i->quantity > 1 ? PersianText::toPersianDigits($i->quantity).'× ' : '').$i->product?->name)->implode(' + ')),
                TextColumn::make('discount_percent')->label('تخفیف')->formatStateUsing(fn (int $state) => PersianText::toPersianDigits($state).'٪'),
                TextColumn::make('price')->label('قیمت ست')->state(function (Bundle $b) {
                    [$full, $price] = Bundles::prices($b);

                    return Money::toman($price).' تومان';
                })->description(fn (Bundle $b) => 'بدون تخفیف '.Money::toman(Bundles::prices($b)[0])),
                ToggleColumn::make('is_active')->label('فعال'),
            ])
            ->recordActions([EditAction::make()->label(''), DeleteAction::make()->label('')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBundles::route('/'),
            'create' => CreateBundle::route('/create'),
            'edit' => EditBundle::route('/{record}/edit'),
        ];
    }
}
