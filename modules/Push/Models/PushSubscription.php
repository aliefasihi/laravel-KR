<?php

/**
 * Khaneh Romantic — A browser that allowed notifications
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Models\Customer;

class PushSubscription extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_sent_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
