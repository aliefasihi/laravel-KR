<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Shipping\Filament\Resources\ShippingMethods\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class CreateShippingMethod extends CreateRecord
{
    protected static string $resource = ShippingMethodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['province_costs'] = ShippingMethodResource::provinceCosts($this->data['province_cost_rows'] ?? []);

        return $data;
    }
}
