<?php

/**
 * Khaneh Romantic — One cart line
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Support;

use Modules\Catalog\Models\ProductVariant;

final class CartLine
{
    public function __construct(
        public readonly ProductVariant $variant,
        public readonly int $quantity,
    ) {}

    public function unitPrice(): int
    {
        return $this->variant->currentPrice();
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }
}
