<?php

/**
 * Khaneh Romantic — «Tell me when it's back in stock» (one SMS, then done)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wishlist\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Modules\Customer\Models\Customer;

class StockAlert extends Model
{
    public const BADGE_KEY = 'badge.stock_alerts';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('notified_at');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
