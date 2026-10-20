<?php

/**
 * Khaneh Romantic — Admin: what shoppers search for (and what they don't find)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * «بی‌نتیجه» rows are products you don't have yet, or words to add as synonyms
 * (config/modules → Search → synonyms) — the most direct demand signal the store has.
 */

namespace Modules\Search\Filament\Resources\SearchTerms;

use App\Core\Support\PersianText;
use App\Filament\Support\NavigationBadge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Search\Filament\Resources\SearchTerms\Pages\ManageSearchTerms;
use Modules\Search\Models\SearchTerm;
use UnitEnum;

class SearchTermResource extends Resource
{
    protected static ?string $model = SearchTerm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 9;

    protected static ?string $modelLabel = 'عبارت جستجو';

    protected static ?string $pluralModelLabel = 'گزارش جستجو';

    protected static ?string $slug = 'search-terms';

    public static function canCreate(): bool
    {
        return false;
    }

    /** Badge: searches that found nothing during the last 30 days. */
    public static function getNavigationBadge(): ?string
    {
        return NavigationBadge::count('admin.badge.search-terms', fn () => SearchTerm::query()
            ->where('last_results', 0)->where('last_searched_at', '>=', now()->subDays(30))->count());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'جستجوهای بی‌نتیجه در ۳۰ روز اخیر';
    }

    public static function table(Table $table): Table
    {
        $fa = fn ($state) => PersianText::toPersianDigits(number_format((int) $state));

        return $table
            ->defaultSort('searches', 'desc')
            ->columns([
                TextColumn::make('term')->label('عبارت')->searchable()->weight('bold'),
                TextColumn::make('searches')->label('تعداد جستجو')->sortable()->formatStateUsing($fa),
                TextColumn::make('last_results')->label('نتیجه (آخرین بار)')->sortable()->formatStateUsing($fa)
                    ->badge()->color(fn (int $state) => $state === 0 ? 'danger' : 'success'),
                TextColumn::make('zero_results')->label('دفعات بی‌نتیجه')->sortable()->formatStateUsing($fa)->toggleable(),
                TextColumn::make('last_searched_at')->label('آخرین جستجو')->sortable()
                    ->jalali(),
            ])
            ->filters([
                Filter::make('zero')->label('فقط بی‌نتیجه‌ها')->query(fn (Builder $query) => $query->where('last_results', 0))->default(),
                Filter::make('recent')->label('۳۰ روز اخیر')->query(fn (Builder $query) => $query->where('last_searched_at', '>=', now()->subDays(30))),
            ])
            ->recordActions([
                Action::make('try')->label('امتحان در سایت')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (SearchTerm $record) => route('catalog.search', ['q' => $record->term]), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSearchTerms::route('/')];
    }
}
