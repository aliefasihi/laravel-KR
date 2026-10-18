<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Filament\Resources\Payments\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Payment\Filament\Resources\Payments\PaymentResource;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
}
