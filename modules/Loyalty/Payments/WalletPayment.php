<?php

/**
 * Khaneh Romantic — «Paid entirely from the wallet»: offered only when nothing is left to pay
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Payments;

use Modules\Checkout\Support\Pricing\Quote;
use Modules\Loyalty\Support\Loyalty;
use Modules\Order\Models\Order;
use Modules\Order\Payments\SettlesZeroTotal;
use Modules\Order\Support\OrderWorkflow;

class WalletPayment implements SettlesZeroTotal
{
    public function code(): string
    {
        return 'wallet';
    }

    public function title(): string
    {
        return 'کیف پول';
    }

    public function description(): ?string
    {
        return 'همه‌ی مبلغ از اعتبار کیف پول شما پرداخت می‌شود.';
    }

    public function availableFor(Quote $quote): bool
    {
        return Loyalty::on('wallet') && $quote->credit > 0;
    }

    public function initialStatus(): string
    {
        return 'pending';
    }

    public function start(Order $order): ?string
    {
        app(OrderWorkflow::class)->markPaid($order, 'پرداخت کامل از کیف پول.');

        return null;
    }
}
