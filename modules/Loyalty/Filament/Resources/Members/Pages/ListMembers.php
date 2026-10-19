<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Members\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Loyalty\Filament\Resources\Members\MemberResource;
use Modules\Loyalty\Filament\Widgets\LoyaltyOverview;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderWidgets(): array
    {
        return [LoyaltyOverview::class];
    }
}
