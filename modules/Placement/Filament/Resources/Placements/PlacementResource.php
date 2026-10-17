<?php

/**
 * Khaneh Romantic — Admin: banner slots (the spec the super-app designs against)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\Filament\Resources\Placements;

use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Placement\Filament\Resources\Placements\Pages\ManagePlacements;
use Modules\Placement\Models\Placement;
use UnitEnum;

class PlacementResource extends Resource
{
    protected static ?string $model = Placement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'جایگاه بنر';

    protected static ?string $pluralModelLabel = 'جایگاه‌های بنر';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->label('کلید')
                ->required()
                ->regex('/^[a-z0-9]+(\.[a-z0-9-]+)+$/')
                ->unique(ignoreRecord: true)
                ->extraInputAttributes(['dir' => 'ltr'])
                ->helperText('مثل home.hero یا category.header — در قالب با <x-placement key="…"/> استفاده می‌شود.'),
            TextInput::make('name')->label('نام')->required(),
            Select::make('context_type')->label('وابسته به')->options(Placement::CONTEXTS)->placeholder('سراسری'),
            Fieldset::make('ابعاد دسکتاپ (پیکسل)')->schema([
                TextInput::make('desktop_width')->label('عرض')->numeric()->required(),
                TextInput::make('desktop_height')->label('ارتفاع')->numeric()->required(),
            ]),
            Fieldset::make('ابعاد موبایل (اختیاری)')->schema([
                TextInput::make('mobile_width')->label('عرض')->numeric(),
                TextInput::make('mobile_height')->label('ارتفاع')->numeric(),
            ]),
            TextInput::make('max_bytes')->label('حداکثر حجم (بایت)')->numeric()->default(300000),
            TextInput::make('max_items')->label('حداکثر بنر همزمان')->numeric()->default(1),
            Toggle::make('is_active')->label('فعال')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('کلید')->fontFamily(FontFamily::Mono)->searchable(),
                TextColumn::make('name')->label('نام')->searchable(),
                TextColumn::make('size')->label('دسکتاپ')
                    ->state(fn (Placement $record) => PersianText::toPersianDigits("{$record->desktop_width}×{$record->desktop_height}")),
                TextColumn::make('context_type')->label('وابسته به')
                    ->formatStateUsing(fn (?string $state) => Placement::CONTEXTS[$state] ?? 'سراسری')->placeholder('سراسری'),
                TextColumn::make('banners_count')->label('بنرها')->counts('banners'),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePlacements::route('/')];
    }
}
