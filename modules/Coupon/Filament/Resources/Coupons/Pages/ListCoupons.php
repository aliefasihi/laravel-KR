<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Filament\Resources\Coupons\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Coupon\Filament\Resources\Coupons\CouponResource;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('کد تخفیف تازه')];
    }
}
