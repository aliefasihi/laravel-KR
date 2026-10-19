<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Filament\Resources\Contacts\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Crm\Filament\Resources\Contacts\ContactResource;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string
    {
        return $this->record->displayName();
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
