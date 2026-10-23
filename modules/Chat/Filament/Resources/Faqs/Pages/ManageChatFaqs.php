<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Filament\Resources\Faqs\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Chat\Filament\Resources\Faqs\ChatFaqResource;

class ManageChatFaqs extends ManageRecords
{
    protected static string $resource = ChatFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('جواب آماده‌ی جدید')];
    }
}
