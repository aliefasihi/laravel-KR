<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Questions\Filament\Resources\Questions\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Questions\Filament\Resources\Questions\QuestionResource;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;
}
