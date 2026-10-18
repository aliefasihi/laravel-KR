<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Reviews\Filament\Resources\Reviews\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Reviews\Filament\Resources\Reviews\ReviewResource;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
}
