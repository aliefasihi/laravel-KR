<?php

/**
 * Khaneh Romantic — Admin: club levels (name, from how much spent, points multiplier, perks)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Levels;

use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Filament\Resources\Levels\Pages\ManageLevels;
use Modules\Loyalty\Models\Level;
use UnitEnum;

class LevelResource extends Resource
{
    protected static ?string $model = Level::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'باشگاه مشتریان';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'سطح';

    protected static ?string $pluralModelLabel = 'سطح‌ها';

    protected static ?string $slug = 'loyalty/levels';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('نام')->required()->maxLength(60),
                ColorPicker::make('color')->label('رنگ')->required(),
                TextInput::make('min_spent')->label('از مجموع خرید')->numeric()->minValue(0)->suffix('تومان')->required()
                    ->formatStateUsing(fn ($state) => $state !== null ? intdiv((int) $state, 10) : 0)
                    ->dehydrateStateUsing(fn ($state) => (int) $state * 10)
                    ->helperText('خریدهای «تکمیل شده»، کالاها پس از تخفیف'),
                TextInput::make('points_multiplier')->label('ضریب امتیاز')->numeric()->minValue(0)->maxValue(10)->step(0.05)->default(1)->required(),
                Textarea::make('perks')->label('مزایا (هر کدام در یک خط؛ به مشتری نمایش داده می‌شود)')->rows(3)->columnSpanFull(),
                TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
                Toggle::make('is_active')->label('فعال')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('min_spent')
            ->reorderable('sort')
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->label('سطح')->weight('bold'),
                TextColumn::make('min_spent')->label('از خرید (تومان)')->toman(),
                TextColumn::make('points_multiplier')->label('ضریب امتیاز')->formatStateUsing(fn ($state) => '×'.PersianText::toPersianDigits(rtrim(rtrim(number_format((float) $state, 2), '0'), '.'))),
                TextColumn::make('members')->label('اعضا')->state(fn (Level $l) => PersianText::toPersianDigits(DB::table('loyalty_customer_levels')->where('level_id', $l->id)->count())),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLevels::route('/')];
    }
}
