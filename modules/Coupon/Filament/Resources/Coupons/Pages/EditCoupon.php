<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Filament\Resources\Coupons\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Coupon\Filament\Resources\Coupons\CouponResource;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;
}
