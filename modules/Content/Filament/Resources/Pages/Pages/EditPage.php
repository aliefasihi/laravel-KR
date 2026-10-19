<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Modules\Content\Filament\Resources\Pages\PageResource;
use Modules\Content\Support\PageBlocks;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label('مشاهده‌ی صفحه')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => $this->record->url(), shouldOpenInNewTab: true),
            Action::make('reset')->label('برگرداندن به محتوای اولیه')->icon('heroicon-o-arrow-uturn-right')->color('gray')
                ->requiresConfirmation()->modalDescription('همه‌ی بلوک‌های فعلی این صفحه با محتوای اولیه جایگزین می‌شوند.')
                ->action(function () {
                    $this->record->update(['blocks' => PageBlocks::defaults($this->record->key)]);
                    $this->fillForm();
                }),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return null;   // stay on the editor after saving
    }
}
