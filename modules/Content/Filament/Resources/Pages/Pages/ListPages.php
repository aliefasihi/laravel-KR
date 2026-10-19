<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Content\Filament\Resources\Pages\PageResource;
use Modules\Content\Models\Page;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    public function mount(): void
    {
        Page::ensureSystemPages();   // About / Contact exist with their starter blocks
        parent::mount();
    }
}
