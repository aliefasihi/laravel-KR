<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Members\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Loyalty\Filament\Resources\Members\MemberResource;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    public function getTitle(): string
    {
        return 'باشگاه — '.$this->record->displayName();
    }

    protected function getHeaderActions(): array
    {
        return [MemberResource::walletAction(), MemberResource::pointsAction()];
    }
}
