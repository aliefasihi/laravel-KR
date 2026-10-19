<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Filament\Resources\Contacts\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Crm\Filament\Resources\Contacts\ContactResource;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('مخاطب تازه')->mutateDataUsing(fn (array $data) => $data + ['source' => 'manual', 'first_seen_at' => now(), 'last_seen_at' => now()]),
            Action::make('export')->label('خروجی CSV')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => ContactResource::export($this->getFilteredSortedTableQuery()->lazyById(500))),
        ];
    }
}
