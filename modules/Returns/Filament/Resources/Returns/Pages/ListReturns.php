<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Filament\Resources\Returns\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Returns\Filament\Resources\Returns\ReturnResource;

class ListReturns extends ListRecords
{
    protected static string $resource = ReturnResource::class;
}
