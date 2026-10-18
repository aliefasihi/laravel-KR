<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
