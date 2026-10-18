<?php

/**
 * Khaneh Romantic — A way to pay (registered by the Payment module)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Payments;

use Modules\Checkout\Support\Pricing\Quote;
use Modules\Order\Models\Order;

interface PaymentMethod
{
    public function code(): string;

    public function title(): string;

    public function description(): ?string;

    /** Offered for this cart/address? (e.g. COD only in Tehran, up to an amount) */
    public function availableFor(Quote $quote): bool;

    /** Status a newly placed order starts in: pending (pay online) | on_hold | processing. */
    public function initialStatus(): string;

    /**
     * Start paying: a URL to send the shopper to (bank gateway), or null to show the order page
     * (card-to-card instructions, cash on delivery).
     */
    public function start(Order $order): ?string;
}
