<?php

/**
 * Khaneh Romantic — An order note: system log, private admin note or a note to the customer
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = ['private' => 'یادداشت داخلی', 'customer' => 'پیام به مشتری', 'system' => 'سیستم'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
