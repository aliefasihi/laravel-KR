<?php

/**
 * Khaneh Romantic — A product a customer saved (and is told about when it gets cheaper)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wishlist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Modules\Customer\Models\Customer;

class WishlistItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['watched_price' => 'integer', 'price_notified_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
