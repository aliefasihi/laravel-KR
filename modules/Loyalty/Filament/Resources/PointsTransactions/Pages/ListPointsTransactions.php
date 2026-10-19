<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\PointsTransactions\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Loyalty\Filament\Resources\PointsTransactions\PointsTransactionResource;

class ListPointsTransactions extends ListRecords
{
    protected static string $resource = PointsTransactionResource::class;
}
