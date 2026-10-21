<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bundles\Filament\Resources\Bundles\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Bundles\Filament\Resources\Bundles\BundleResource;

class CreateBundle extends CreateRecord
{
    protected static string $resource = BundleResource::class;
}
