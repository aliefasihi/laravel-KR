<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Filament\Resources\Campaigns\Pages;

use App\Core\Support\PersianText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Push\Filament\Resources\Campaigns\PushCampaignResource;
use Modules\Push\Models\PushSubscription;

class ListPushCampaigns extends ListRecords
{
    protected static string $resource = PushCampaignResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $all = PushSubscription::query()->count();
        $customers = PushSubscription::query()->whereNotNull('customer_id')->count();

        return PersianText::toPersianDigits($all).' مرورگر عضو نوتیفیکیشن ('.PersianText::toPersianDigits($customers).' مشتری وارد شده). ارسال در پس‌زمینه و دقیقه‌ای یک بار انجام می‌شود.';
    }

    protected function getHeaderActions(): array
    {
        return [PushCampaignResource::testAction(), CreateAction::make()->label('نوتیفیکیشن جدید')];
    }
}
