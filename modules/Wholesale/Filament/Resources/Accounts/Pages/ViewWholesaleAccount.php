<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Filament\Resources\Accounts\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Wholesale\Filament\Resources\Accounts\WholesaleAccountResource;

class ViewWholesaleAccount extends ViewRecord
{
    protected static string $resource = WholesaleAccountResource::class;

    protected function getHeaderActions(): array
    {
        return array_map(fn ($action) => $action->after(fn () => $this->record->refresh()), WholesaleAccountResource::steps());
    }
}
