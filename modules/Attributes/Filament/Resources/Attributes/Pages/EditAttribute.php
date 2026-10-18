<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Filament\Resources\Attributes\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Attributes\Filament\Resources\Attributes\AttributeResource;

class EditAttribute extends EditRecord
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // paste many values at once (one per line) instead of clicking «افزودن مقدار» each time
            Action::make('bulkValues')->label('افزودن چند مقدار با هم')->icon(Heroicon::OutlinedQueueList)->color('gray')
                ->schema([Textarea::make('lines')->label('هر مقدار در یک خط')->rows(10)->required()])
                ->action(function (array $data) {
                    $existing = $this->record->values()->pluck('value')->all();
                    $next = (int) $this->record->values()->max('sort');
                    $added = 0;
                    foreach (preg_split('/\R/u', $data['lines']) as $line) {
                        $value = trim($line);
                        if ($value === '' || in_array($value, $existing, true)) {
                            continue;
                        }
                        $this->record->values()->create(['value' => $value, 'sort' => ++$next]);
                        $existing[] = $value;
                        $added++;
                    }
                    $this->refreshFormData(['values']);
                    Notification::make()->title("{$added} مقدار اضافه شد")->success()->send();
                }),
            DeleteAction::make(),
        ];
    }
}
