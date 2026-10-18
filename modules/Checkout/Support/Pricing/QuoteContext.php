<?php

/**
 * Khaneh Romantic — What a price quote is computed for (cart + shopper's choices)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Support\Pricing;

use Illuminate\Support\Collection;
use Modules\Checkout\Support\CartLine;

final class QuoteContext
{
    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public function __construct(
        public readonly Collection $lines,
        public readonly ?int $customerId = null,
        public readonly ?string $mobile = null,
        public readonly ?string $province = null,
        public readonly ?string $city = null,
        public readonly ?string $couponCode = null,
        public readonly ?string $shippingMethod = null,
        public readonly ?string $paymentMethod = null,
    ) {}

    public function subtotal(): int
    {
        return (int) $this->lines->sum(fn (CartLine $line) => $line->total());
    }

    /** Sum of regular prices − what is paid: the shopper's savings from sales. */
    public function saleSavings(): int
    {
        return (int) $this->lines->sum(fn (CartLine $line) => max(0, $line->variant->price - $line->unitPrice()) * $line->quantity);
    }

    public function itemCount(): int
    {
        return (int) $this->lines->sum(fn (CartLine $line) => $line->quantity);
    }

    public function with(array $changes): self
    {
        return new self(...array_merge(get_object_vars($this), $changes));
    }
}
