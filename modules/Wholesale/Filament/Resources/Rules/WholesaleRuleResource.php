<?php

/**
 * Khaneh Romantic — Admin: wholesale price rules for a category, a brand or one product
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Filament\Resources\Rules;

use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Wholesale\Filament\Resources\Rules\Pages\ManageWholesaleRules;
use Modules\Wholesale\Models\WholesaleRule;
use Modules\Wholesale\Support\Wholesale;
use UnitEnum;

class WholesaleRuleResource extends Resource
{
    protected static ?string $model = WholesaleRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 17;

    protected static ?string $navigationLabel = 'قیمت‌های عمده';

    protected static ?string $modelLabel = 'قانون قیمت عمده';

    protected static ?string $pluralModelLabel = 'قوانین قیمت عمده';

    protected static ?string $slug = 'wholesale-rules';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ToggleButtons::make('scope')->label('برای')->options(WholesaleRule::SCOPES)->inline()->required()->default('category')->live()
                ->afterStateUpdated(fn (Set $set) => $set('target_id', null)),
            Select::make('target_id')->label(fn (Get $get) => WholesaleRule::SCOPES[$get('scope')] ?? 'کدام')->required()->searchable()
                ->getSearchResultsUsing(fn (string $search, Get $get) => match ($get('scope')) {
                    'category' => array_slice(array_filter(self::categoryOptions(), fn ($label) => mb_stripos($label, $search) !== false), 0, 40, true),
                    'brand' => Brand::query()->where(fn ($q) => $q->where('name_fa', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"))->limit(30)->pluck('name_fa', 'id')->all(),
                    default => Product::query()->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))->limit(30)->pluck('name', 'id')->all(),
                })
                ->getOptionLabelUsing(fn ($value, Get $get) => (new WholesaleRule(['scope' => $get('scope'), 'target_id' => $value]))->targetName() ?: null)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('scope', $get('scope')))
                ->validationMessages(['unique' => 'برای این مورد قبلاً قانون ساخته شده؛ همان را ویرایش کنید.'])
                ->helperText(fn (Get $get) => match ($get('scope')) {
                    'category' => 'زیردسته‌ها هم شامل می‌شوند، مگر خودشان قانون جدا داشته باشند.',
                    'brand' => 'برای محصولاتی که قانون محصول یا دسته ندارند.',
                    default => 'قوی‌ترین قانون: روی همین محصول، هر دسته و برندی داشته باشد.',
                }),
            Repeater::make('tiers')->label('پله‌های قیمت همکار')
                ->helperText('خالی بگذارید = این محصولات قیمت همکار ندارند (مثلاً کالاهای کم‌سود).')
                ->schema([
                    TextInput::make('min_qty')->label('از تعداد')->integer()->minValue(1)->required()->suffix('عدد'),
                    TextInput::make('percent')->label('درصد زیر قیمت اصلی')->numeric()->minValue(0)->maxValue(90)->required()->suffix('٪'),
                ])->columns(2)->defaultItems(1)->addActionLabel('پله‌ی جدید')->reorderable(false),
            TextInput::make('note')->label('یادداشت (فقط برای شما)')->maxLength(190),
            Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('scope')->label('نوع')->badge()->formatStateUsing(fn ($state) => WholesaleRule::SCOPES[$state] ?? $state)
                    ->color(fn ($state) => ['product' => 'primary', 'category' => 'info', 'brand' => 'warning'][$state] ?? 'gray'),
                TextColumn::make('target_id')->label('مورد')->state(fn (WholesaleRule $r) => $r->targetName() ?: '(حذف شده)')->weight('bold')
                    ->description(fn (WholesaleRule $r) => $r->note),
                TextColumn::make('tiers')->label('پله‌ها')->state(function (WholesaleRule $r) {
                    $tiers = Wholesale::clean((array) $r->tiers);

                    return $tiers === []
                        ? 'بدون قیمت همکار'
                        : collect($tiers)->map(fn ($t) => 'از '.PersianText::toPersianDigits($t['min_qty']).' عدد '.Wholesale::percent($t['percent']).'٪')->implode(' · ');
                }),
                ToggleColumn::make('is_active')->label('فعال'),
            ])
            ->filters([SelectFilter::make('scope')->label('نوع')->options(WholesaleRule::SCOPES)])
            ->recordActions([EditAction::make()->label(''), DeleteAction::make()->label('')])
            ->emptyStateHeading('هنوز قانونی نیست')
            ->emptyStateDescription('تا وقتی قانونی نسازید، پله‌های پیش‌فرض «تنظیمات ← فروش عمده» برای همه اعمال می‌شود.');
    }

    /** @return array<int, string> «والد › فرزند» labels, in tree order */
    private static function categoryOptions(): array
    {
        $all = Category::query()->orderBy('sort')->get(['id', 'name', 'parent_id'])->keyBy('id');
        $label = function (Category $c) use ($all): string {
            $names = [];
            for ($node = $c, $guard = 0; $node && $guard < 8; $node = $all->get($node->parent_id), $guard++) {
                array_unshift($names, $node->name);
            }

            return implode(' › ', $names);
        };

        return $all->map($label)->sort()->all();
    }

    public static function getPages(): array
    {
        return ['index' => ManageWholesaleRules::route('/')];
    }
}
