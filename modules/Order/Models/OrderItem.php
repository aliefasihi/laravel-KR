<?php

/**
 * Khaneh Romantic — A line of an order (a copy of the product as it was bought)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['options' => 'array', 'regular_price' => 'integer', 'unit_price' => 'integer', 'quantity' => 'integer', 'total' => 'integer', 'refunded_quantity' => 'integer'];
    }

    /** The columns of a new line: the product as it is right now, at the given price. */
    public static function snapshot(ProductVariant $variant, int $unitPrice, int $quantity, ?int $regularPrice = null): array
    {
        return [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'name' => $variant->product->name,
            'sku' => $variant->sku,
            'brand' => $variant->product->brand?->name_fa,
            'options' => $variant->options ?: null,
            'regular_price' => $regularPrice ?? $variant->price,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total' => $unitPrice * $quantity,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
