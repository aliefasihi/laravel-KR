<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Filament\Resources\SearchTerms\Pages;

use Filament\Resources\Pages\ManageRecords;
use Modules\Search\Filament\Resources\SearchTerms\SearchTermResource;

class ManageSearchTerms extends ManageRecords
{
    protected static string $resource = SearchTermResource::class;
}
