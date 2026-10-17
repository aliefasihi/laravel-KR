<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Filament\Resources\Posts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Content\Filament\Resources\Posts\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
