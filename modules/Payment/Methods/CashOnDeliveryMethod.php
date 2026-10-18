<?php

/**
 * Khaneh Romantic — «پرداخت در محل»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Methods;

use App\Core\Settings\Settings;
use App\Core\Support\Money;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Order\Models\Order;
use Modules\Order\Payments\PaymentMethod;

class CashOnDeliveryMethod implements PaymentMethod
{
    public function code(): string
    {
        return 'cod';
    }

    public function title(): string
    {
        return (string) Settings::get('payment.cod_title', 'پرداخت در محل (کارت‌خوان هنگام تحویل)');
    }

    public function description(): ?string
    {
        $max = (int) Settings::get('payment.cod_max');

        return 'هنگام تحویل سفارش با کارت‌خوان پرداخت کنید.'.($max ? ' برای سفارش‌های تا '.Money::toman($max).' تومان.' : '');
    }

    public function availableFor(Quote $quote): bool
    {
        if (! Settings::get('payment.cod_enabled')) {
            return false;
        }
        $provinces = (array) Settings::get('payment.cod_provinces', []);
        if ($provinces && ! in_array($quote->context->province, $provinces, true)) {
            return false;
        }
        $max = (int) Settings::get('payment.cod_max');

        return ! ($max && $quote->total() > $max);
    }

    public function initialStatus(): string
    {
        return 'processing';
    }

    public function start(Order $order): ?string
    {
        return null;
    }
}
