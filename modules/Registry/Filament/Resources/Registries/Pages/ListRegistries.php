<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Filament\Resources\Registries\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Registry\Filament\Resources\Registries\RegistryResource;

class ListRegistries extends ListRecords
{
    protected static string $resource = RegistryResource::class;
}
