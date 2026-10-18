<?php

/**
 * Khaneh Romantic — A priced cart: subtotal, discounts, shipping, total (all Rials)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Filled by the modules that take part (Quoter::extend): Coupon adds a discount (and may make
 * shipping free), Shipping adds the shipping cost, Payment may add a fee… The cart page,
 * checkout and the placed order all read the same quote, so the numbers never disagree.
 */

namespace Modules\Checkout\Support\Pricing;

final class Quote
{
    public int $discount = 0;

    public int $shipping = 0;

    public int $fees = 0;

    public bool $freeShipping = false;

    /** Shipping is chosen and possible for the address (Shipping module sets it). */
    public bool $shippingReady = false;

    /** @var list<array{key: string, label: string, amount: int, note: ?string}> rows shown under the subtotal (amount < 0 = discount) */
    public array $rows = [];

    /** @var array<string, string> key => message, e.g. ['coupon' => 'این کد منقضی شده است.'] */
    public array $messages = [];

    /** @var array<string, mixed> data a module wants to keep with the order (e.g. coupon id, shipping method) */
    public array $meta = [];

    public function __construct(public readonly QuoteContext $context, public readonly int $subtotal) {}

    public function addDiscount(string $key, string $label, int $amount, ?string $note = null): void
    {
        $amount = max(0, min($amount, $this->subtotal - $this->discount));
        $this->discount += $amount;
        $this->rows[] = ['key' => $key, 'label' => $label, 'amount' => -$amount, 'note' => $note];
    }

    public function setShipping(string $label, int $amount, ?string $note = null): void
    {
        $this->shipping = max(0, $amount);
        $this->shippingReady = true;
        $this->rows[] = ['key' => 'shipping', 'label' => $label, 'amount' => $this->shipping, 'note' => $note];
    }

    public function addFee(string $key, string $label, int $amount, ?string $note = null): void
    {
        $this->fees += $amount;
        $this->rows[] = ['key' => $key, 'label' => $label, 'amount' => $amount, 'note' => $note];
    }

    public function total(): int
    {
        return max(0, $this->subtotal - $this->discount + $this->shipping + $this->fees);
    }
}
