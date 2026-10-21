<?php

/**
 * Khaneh Romantic — A signed-in shopper's cart that may need a reminder
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\AbandonedCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Models\Customer;

class AbandonedCart extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'integer',
            'order_total' => 'integer',
            'active_at' => 'datetime',
            'reminded_at' => 'datetime',
            'clicked_at' => 'datetime',
            'recovered_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function count(): int
    {
        return array_sum(array_map('intval', $this->items ?? []));
    }

    public function status(): string
    {
        return match (true) {
            $this->recovered_at !== null => 'recovered',
            $this->reminded_at !== null => 'reminded',
            default => 'open',
        };
    }
}
