<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Filament\Resources\AttributeGroups\Pages;

use App\Core\Support\StoreCache;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Attributes\Filament\Resources\AttributeGroups\AttributeGroupResource;

class EditAttributeGroup extends EditRecord
{
    protected static string $resource = AttributeGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** Categories are a pivot (no model events): refresh templates once they are synced. */
    protected function afterSave(): void
    {
        StoreCache::flush();
    }
}
