<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Filament\Resources\Customers\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Customer\Filament\Resources\Customers\CustomerResource;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;
}
