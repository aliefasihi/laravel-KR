<?php

/**
 * Khaneh Romantic — A member's wallet ledger
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Members\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class WalletRelationManager extends RelationManager
{
    protected static string $relationship = 'loyaltyWalletTransactions';

    protected static ?string $title = 'کیف پول';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns(WalletTransactionColumns::make());
    }
}
