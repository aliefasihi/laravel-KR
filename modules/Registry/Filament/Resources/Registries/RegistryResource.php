<?php

/**
 * Khaneh Romantic — Admin: wedding lists (who made one, how full it is, a link to it)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Filament\Resources\Registries;

use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Registry\Filament\Resources\Registries\Pages\ListRegistries;
use Modules\Registry\Models\Registry;
use UnitEnum;

class RegistryResource extends Resource
{
    protected static ?string $model = Registry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 29;

    protected static ?string $modelLabel = 'لیست جهیزیه';

    protected static ?string $pluralModelLabel = 'لیست‌های جهیزیه';

    protected static ?string $slug = 'registries';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer')->withCount('items'))
            ->columns([
                TextColumn::make('title')->label('عنوان')->searchable()->description(fn (Registry $r) => $r->names),
                TextColumn::make('customer.mobile')->label('سازنده')->searchable()->formatStateUsing(fn (?string $state) => PersianText::toPersianDigits((string) $state)),
                TextColumn::make('items_count')->label('اقلام')->faDigits()->sortable(),
                TextColumn::make('event_date')->label('تاریخ مراسم')->jalali()->placeholder('—')->sortable(),
                IconColumn::make('is_public')->label('عمومی')->boolean(),
                TextColumn::make('created_at')->label('ساخته‌شده')->jalali()->sortable(),
            ])
            ->recordActions([
                Action::make('open')->label('دیدن لیست')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Registry $r) => $r->url(), shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListRegistries::route('/')];
    }
}
