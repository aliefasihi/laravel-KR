<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Filament\Resources\Coupons\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Coupon\Filament\Resources\Coupons\CouponResource;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;
}
