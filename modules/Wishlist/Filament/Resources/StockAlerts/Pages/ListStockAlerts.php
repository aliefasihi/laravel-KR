<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wishlist\Filament\Resources\StockAlerts\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Wishlist\Filament\Resources\StockAlerts\StockAlertResource;

class ListStockAlerts extends ListRecords
{
    protected static string $resource = StockAlertResource::class;
}
