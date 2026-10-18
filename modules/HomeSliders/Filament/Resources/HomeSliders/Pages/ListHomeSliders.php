<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\HomeSliders\Filament\Resources\HomeSliders\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\HomeSliders\Filament\Resources\HomeSliders\HomeSliderResource;

class ListHomeSliders extends ListRecords
{
    protected static string $resource = HomeSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('دیدن صفحه‌ی اصلی')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(route('home'), shouldOpenInNewTab: true),
            CreateAction::make()->label('اسلایدر تازه'),
        ];
    }
}
