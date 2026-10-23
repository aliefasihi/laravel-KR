<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Filament\Resources\Accounts\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Wholesale\Filament\Resources\Accounts\WholesaleAccountResource;

class ListWholesaleAccounts extends ListRecords
{
    protected static string $resource = WholesaleAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [WholesaleAccountResource::addAction()];
    }
}
