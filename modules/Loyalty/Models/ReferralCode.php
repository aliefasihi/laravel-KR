<?php

/**
 * Khaneh Romantic — A customer's personal referral code
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'loyalty_referral_codes';

    protected $guarded = ['id'];
}
