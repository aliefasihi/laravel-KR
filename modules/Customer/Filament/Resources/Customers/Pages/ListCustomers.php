<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Filament\Resources\Customers\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Customer\Filament\Resources\Customers\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
