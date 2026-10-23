<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Filament\Resources\Campaigns\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Push\Filament\Resources\Campaigns\PushCampaignResource;

class EditPushCampaign extends EditRecord
{
    protected static string $resource = PushCampaignResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
