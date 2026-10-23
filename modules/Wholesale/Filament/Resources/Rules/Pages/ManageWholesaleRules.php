<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Filament\Resources\Rules\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Wholesale\Filament\Resources\Rules\WholesaleRuleResource;

class ManageWholesaleRules extends ManageRecords
{
    protected static string $resource = WholesaleRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('قانون جدید')];
    }
}
