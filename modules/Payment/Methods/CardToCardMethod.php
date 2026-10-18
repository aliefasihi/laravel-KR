<?php

/**
 * Khaneh Romantic — «کارت به کارت»: transfer, then upload the receipt; staff confirm it
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Methods;

use App\Core\Settings\Settings;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Order\Models\Order;
use Modules\Order\Payments\PaymentMethod;

class CardToCardMethod implements PaymentMethod
{
    public function code(): string
    {
        return 'card';
    }

    public function title(): string
    {
        return (string) Settings::get('payment.card_title', 'کارت به کارت');
    }

    public function description(): ?string
    {
        return 'مبلغ را به کارت فروشگاه واریز کنید و تصویر رسید را در صفحه‌ی سفارش بارگذاری کنید. سفارش پس از تأیید رسید ارسال می‌شود.';
    }

    public function availableFor(Quote $quote): bool
    {
        return (bool) Settings::get('payment.card_enabled') && filled(Settings::get('payment.card_number'));
    }

    public function initialStatus(): string
    {
        return 'on_hold';
    }

    public function start(Order $order): ?string
    {
        return null; // the order page shows the card and the receipt form
    }
}
