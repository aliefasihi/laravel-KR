<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Filament\Resources\Campaigns\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Push\Filament\Resources\Campaigns\PushCampaignResource;

class CreatePushCampaign extends CreateRecord
{
    protected static string $resource = PushCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['created_by' => auth()->id(), 'status' => 'draft'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
