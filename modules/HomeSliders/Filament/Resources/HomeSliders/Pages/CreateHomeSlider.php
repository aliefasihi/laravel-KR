<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\HomeSliders\Filament\Resources\HomeSliders\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\HomeSliders\Filament\Resources\HomeSliders\HomeSliderResource;

class CreateHomeSlider extends CreateRecord
{
    protected static string $resource = HomeSliderResource::class;
}
