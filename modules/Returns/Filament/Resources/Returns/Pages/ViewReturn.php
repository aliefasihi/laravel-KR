<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Filament\Resources\Returns\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Returns\Filament\Resources\Returns\ReturnResource;

class ViewReturn extends ViewRecord
{
    protected static string $resource = ReturnResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(fn ($action) => $action->after(fn () => $this->record->refresh()), ReturnResource::steps());
    }
}
