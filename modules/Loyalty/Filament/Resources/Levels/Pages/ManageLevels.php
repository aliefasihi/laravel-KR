<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Levels\Pages;

use App\Core\Support\PersianText;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Modules\Loyalty\Filament\Resources\Levels\LevelResource;
use Modules\Loyalty\Support\Levels;

class ManageLevels extends ManageRecords
{
    protected static string $resource = LevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('سطح تازه'),
            Action::make('rebuild')->label('محاسبه‌ی دوباره از سفارش‌ها')->icon('heroicon-o-arrow-path')->color('gray')
                ->requiresConfirmation()->modalDescription('مجموع خرید و سطح همه‌ی مشتریان از روی سفارش‌های «تکمیل شده» دوباره حساب می‌شود (مثلاً بعد از درون‌ریزی سفارش‌های قدیمی).')
                ->action(function () {
                    $n = Levels::rebuild();
                    Notification::make()->success()->title('سطح '.PersianText::toPersianDigits($n).' مشتری به‌روز شد')->send();
                }),
        ];
    }
}
