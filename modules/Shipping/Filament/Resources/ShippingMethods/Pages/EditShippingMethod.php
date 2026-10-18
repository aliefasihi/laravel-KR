<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Shipping\Filament\Resources\ShippingMethods\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class EditShippingMethod extends EditRecord
{
    protected static string $resource = ShippingMethodResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['province_costs'] = ShippingMethodResource::provinceCosts($this->data['province_cost_rows'] ?? []);

        return $data;
    }
}
