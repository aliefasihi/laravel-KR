<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Filament\Resources\Conversations\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Chat\Filament\Resources\Conversations\ChatConversationResource;

class ListChatConversations extends ListRecords
{
    protected static string $resource = ChatConversationResource::class;
}
