<?php

/**
 * Khaneh Romantic — A customer's points (may be negative while a refund is being paid off)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Models\Customer;

class PointsAccount extends Model
{
    protected $table = 'loyalty_points_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['balance' => 'integer'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
