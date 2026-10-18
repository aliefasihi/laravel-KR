<?php

/**
 * Khaneh Romantic — Product variant (price/stock live here; Rials)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use FlushesStoreCache;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'integer',
            'sale_price' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Effective price in Rials, honoring the sale window. */
    public function currentPrice(): int
    {
        $onSale = $this->sale_price !== null
            && ($this->sale_starts_at === null || $this->sale_starts_at->isPast())
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());

        return $onSale ? $this->sale_price : $this->price;
    }
}
