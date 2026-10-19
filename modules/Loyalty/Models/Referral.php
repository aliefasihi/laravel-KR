<?php

/**
 * Khaneh Romantic — «X introduced Y»; rewarded once, when Y's first qualifying order is completed
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Models\Customer;

class Referral extends Model
{
    public const STATUSES = ['pending' => 'در انتظار اولین خرید', 'rewarded' => 'جایزه داده شد', 'reversed' => 'برگشت خورد'];

    public const COLORS = ['pending' => 'warning', 'rewarded' => 'success', 'reversed' => 'gray'];

    protected $table = 'loyalty_referrals';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rewarded_at' => 'datetime', 'referrer_reward' => 'integer', 'referred_reward' => 'integer'];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_id');
    }
}
