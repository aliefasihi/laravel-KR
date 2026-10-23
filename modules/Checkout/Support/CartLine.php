<?php

/**
 * Khaneh Romantic — One cart line
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The unit price is the variant's live price, unless a module set a special one for this shopper
 * (LinePrices: e.g. the wholesale price for an approved wholesale customer).
 */

namespace Modules\Checkout\Support;

use Modules\Catalog\Models\ProductVariant;

final class CartLine
{
    public function __construct(
        public readonly ProductVariant $variant,
        public readonly int $quantity,
        public readonly ?int $price = null,
        public readonly ?string $priceLabel = null,
    ) {}

    public function unitPrice(): int
    {
        return $this->price ?? $this->variant->currentPrice();
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function withPrice(int $price, string $label): self
    {
        return new self($this->variant, $this->quantity, $price, $label);
    }
}
