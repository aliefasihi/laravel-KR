<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Filament\Resources\NewsletterSubscribers\Pages;

use App\Core\Support\PersianText;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Modules\Content\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use Modules\Content\Models\NewsletterSubscriber;
use Modules\Content\Support\Newsletter;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    public function getSubheading(): ?string
    {
        $active = NewsletterSubscriber::query()->active()->selectRaw('channel, count(*) as c')->groupBy('channel')->pluck('c', 'channel');

        return 'فعال: '.PersianText::toPersianDigits((int) ($active['sms'] ?? 0)).' شماره و '.PersianText::toPersianDigits((int) ($active['email'] ?? 0)).' ایمیل';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add')->label('افزودن دستی')->icon('heroicon-o-plus')
                ->schema([Textarea::make('contacts')->label('شماره‌ها یا ایمیل‌ها (هر کدام در یک خط)')->rows(6)->required()])
                ->action(function (array $data) {
                    $added = 0;
                    foreach (preg_split('/[\r\n,،;]+/', $data['contacts']) as $line) {
                        $added += Newsletter::subscribe($line, 'admin')['status'] === 'created' ? 1 : 0;
                    }
                    Notification::make()->title(PersianText::toPersianDigits($added).' عضو تازه اضافه شد')->success()->send();
                }),
            Action::make('export')->label('خروجی CSV')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => NewsletterSubscriberResource::export($this->getFilteredSortedTableQuery()->lazyById(500))),
        ];
    }
}
