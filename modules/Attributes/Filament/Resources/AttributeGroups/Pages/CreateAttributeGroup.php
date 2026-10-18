<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Filament\Resources\AttributeGroups\Pages;

use App\Core\Support\StoreCache;
use Filament\Resources\Pages\CreateRecord;
use Modules\Attributes\Filament\Resources\AttributeGroups\AttributeGroupResource;

class CreateAttributeGroup extends CreateRecord
{
    protected static string $resource = AttributeGroupResource::class;

    /** Categories are a pivot (no model events): refresh templates once they are synced. */
    protected function afterCreate(): void
    {
        StoreCache::flush();
    }
}
