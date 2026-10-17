<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\Filament\Resources\Placements\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Placement\Filament\Resources\Placements\PlacementResource;

class ManagePlacements extends ManageRecords
{
    protected static string $resource = PlacementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
