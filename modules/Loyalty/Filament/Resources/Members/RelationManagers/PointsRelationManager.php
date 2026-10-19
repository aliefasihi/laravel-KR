<?php

/**
 * Khaneh Romantic — A member's points ledger
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Members\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class PointsRelationManager extends RelationManager
{
    protected static string $relationship = 'loyaltyPointsTransactions';

    protected static ?string $title = 'امتیاز';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns(WalletTransactionColumns::points());
    }
}
