<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Filament\Resources\Conversations\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Chat\Filament\Resources\Conversations\ChatConversationResource;

class ViewChatConversation extends ViewRecord
{
    protected static string $resource = ChatConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [ChatConversationResource::faqAction()];
    }
}
