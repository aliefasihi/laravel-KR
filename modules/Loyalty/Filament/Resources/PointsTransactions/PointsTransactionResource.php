<?php

/**
 * Khaneh Romantic — Admin: every points movement (read-only ledger)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\PointsTransactions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Loyalty\Filament\Resources\Members\RelationManagers\WalletTransactionColumns;
use Modules\Loyalty\Filament\Resources\PointsTransactions\Pages\ListPointsTransactions;
use Modules\Loyalty\Models\PointsTransaction;
use UnitEnum;

class PointsTransactionResource extends Resource
{
    protected static ?string $model = PointsTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'باشگاه مشتریان';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'تراکنش امتیاز';

    protected static ?string $pluralModelLabel = 'تراکنش‌های امتیاز';

    protected static ?string $slug = 'loyalty/points';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer'))
            ->columns(WalletTransactionColumns::points(withCustomer: true))
            ->filters([SelectFilter::make('type')->label('نوع')->options(PointsTransaction::TYPES)]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPointsTransactions::route('/')];
    }
}
