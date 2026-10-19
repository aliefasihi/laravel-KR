<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\WalletTransactions\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Modules\Loyalty\Filament\Resources\WalletTransactions\WalletTransactionResource;

class ListWalletTransactions extends ListRecords
{
    protected static string $resource = WalletTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('خروجی CSV')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => WalletTransactionResource::export($this->getFilteredSortedTableQuery()->with('customer')->lazyById(500))),
        ];
    }
}
