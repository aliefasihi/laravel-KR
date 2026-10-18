<?php

/**
 * Khaneh Romantic — «پرداخت آنلاین (زرین‌پال)»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Methods;

use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Log;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Order\Models\Order;
use Modules\Order\Payments\PaymentMethod;
use Modules\Payment\Models\Payment;
use Modules\Payment\Support\ZarinpalClient;
use Throwable;

class ZarinpalMethod implements PaymentMethod
{
    public function code(): string
    {
        return 'zarinpal';
    }

    public function title(): string
    {
        return (string) Settings::get('payment.zarinpal_title', 'پرداخت آنلاین (همه‌ی کارت‌های بانکی)');
    }

    public function description(): ?string
    {
        return 'پرداخت امن از درگاه زرین‌پال با همه‌ی کارت‌های عضو شتاب.';
    }

    public function availableFor(Quote $quote): bool
    {
        return (bool) Settings::get('payment.zarinpal_enabled') && filled(Settings::get('payment.zarinpal_merchant'));
    }

    public function initialStatus(): string
    {
        return 'pending';
    }

    public function client(): ZarinpalClient
    {
        return new ZarinpalClient((string) Settings::get('payment.zarinpal_merchant'), (bool) Settings::get('payment.zarinpal_sandbox'));
    }

    public function start(Order $order): ?string
    {
        Payment::query()->where('order_id', $order->id)->where('method', $this->code())->where('status', 'pending')->update(['status' => 'cancelled']);
        $payment = Payment::create(['order_id' => $order->id, 'method' => $this->code(), 'amount' => $order->total, 'status' => 'pending']);

        try {
            $authority = $this->client()->request(
                $order->total,
                route('payment.zarinpal.callback'),
                'سفارش '.$order->number.' — '.config('khaneh.store.name'),
                $order->customer_mobile,
                (string) $order->number,
            );
        } catch (Throwable $e) {
            Log::warning('Zarinpal: '.$e->getMessage(), ['order' => $order->number]);
            $payment->update(['status' => 'failed', 'meta' => ['error' => $e->getMessage()]]);
            $order->note('اتصال به درگاه زرین‌پال ممکن نشد: '.$e->getMessage());
            session()->flash('status', 'اتصال به درگاه پرداخت ممکن نشد. چند لحظه بعد دوباره «پرداخت آنلاین» را بزنید.');

            return route('customer.orders.show', $order);
        }

        $payment->update(['authority' => $authority]);

        return $this->client()->payUrl($authority);
    }
}
