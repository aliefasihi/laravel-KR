<?php

/**
 * Khaneh Romantic — «خرید اقساطی (اسنپ‌پی)»
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Offered when it's switched on, the total is inside the set range and SnappPay says the amount is
 * eligible (asked once per amount, remembered 10 minutes). The order waits as «pending» until the
 * shopper comes back and the payment is verified and settled.
 */

namespace Modules\Payment\Methods;

use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Order\Models\Order;
use Modules\Order\Payments\PaymentMethod;
use Modules\Payment\Models\Payment;
use Modules\Payment\Support\SnappPayClient;
use Throwable;

class SnappPayMethod implements PaymentMethod
{
    public function code(): string
    {
        return 'snapppay';
    }

    public function title(): string
    {
        return (string) Settings::get('payment.snapppay_title', 'خرید اقساطی با اسنپ‌پی (۴ قسط)');
    }

    public function description(): ?string
    {
        return 'یک‌چهارم مبلغ را حالا بپردازید و بقیه را در سه قسط ماهانه؛ تأیید اعتبار آنی در اسنپ‌پی.';
    }

    public static function enabled(): bool
    {
        return (bool) Settings::get('payment.snapppay_enabled') && filled(Settings::get('payment.snapppay_base_url')) && filled(Settings::get('payment.snapppay_client_id'));
    }

    /** Inside the set range (Rials)? */
    public static function inRange(int $amount): bool
    {
        $min = (int) Settings::get('payment.snapppay_min', 0);
        $max = (int) Settings::get('payment.snapppay_max', 0);

        return $amount > 0 && $amount >= $min && ($max === 0 || $amount <= $max);
    }

    public function availableFor(Quote $quote): bool
    {
        $amount = $quote->total();
        if (! self::enabled() || ! self::inRange($amount)) {
            return false;
        }

        return Cache::remember('payment.snapppay.eligible.'.$amount, 600, function () use ($amount) {
            try {
                return $this->client()->eligible($amount)['eligible'];
            } catch (Throwable $e) {
                Log::warning('SnappPay eligible: '.$e->getMessage());

                return false;
            }
        });
    }

    public function initialStatus(): string
    {
        return 'pending';
    }

    public function client(): SnappPayClient
    {
        return new SnappPayClient(
            (string) Settings::get('payment.snapppay_base_url'),
            (string) Settings::get('payment.snapppay_client_id'),
            (string) Settings::get('payment.snapppay_client_secret'),
            (string) Settings::get('payment.snapppay_username'),
            (string) Settings::get('payment.snapppay_password'),
        );
    }

    /** The «token» request: totals before discount + the discount, and every line of the order. */
    public function payload(Order $order, string $transactionId): array
    {
        $order->loadMissing('items');
        $amount = $order->total + $order->discount;
        $category = Str::limit(config('khaneh.store.name').' — لوازم خانه', 60, '');

        return [
            'amount' => $amount,
            'discountAmount' => (int) $order->discount,
            'externalSourceAmount' => 0,
            'mobile' => SnappPayClient::mobile($order->customer_mobile ?: $order->recipient_mobile),
            'paymentMethodTypeDto' => 'INSTALLMENT',
            'returnURL' => route('payment.snapppay.callback'),
            'transactionId' => $transactionId,
            'cartList' => [[
                'cartId' => (string) $order->number,
                'totalAmount' => $amount,
                'shippingAmount' => (int) $order->shipping_total,
                'isShipmentIncluded' => true,
                'taxAmount' => 0,
                'isTaxIncluded' => true,
                'cartItems' => $order->items->map(fn ($line) => [
                    'id' => (string) ($line->product_id ?? $line->id),
                    'name' => Str::limit($line->name, 120, ''),
                    'count' => (int) $line->quantity,
                    'amount' => (int) $line->unit_price,
                    'category' => $category,
                    'commissionType' => 100,
                ])->values()->all(),
            ]],
        ];
    }

    public function start(Order $order): ?string
    {
        Payment::query()->where('order_id', $order->id)->where('method', $this->code())->where('status', 'pending')->update(['status' => 'cancelled']);

        // 10-digit id, unique per attempt (SnappPay's transactionId)
        do {
            $transactionId = (string) random_int(1_000_000_000, 9_999_999_999);
        } while (Payment::query()->where('authority', $transactionId)->exists());

        $payment = Payment::create(['order_id' => $order->id, 'method' => $this->code(), 'amount' => $order->total, 'status' => 'pending', 'authority' => $transactionId]);

        try {
            $result = $this->client()->token($this->payload($order, $transactionId));
        } catch (Throwable $e) {
            Log::warning('SnappPay token: '.$e->getMessage(), ['order' => $order->number]);
            $payment->update(['status' => 'failed', 'meta' => ['error' => $e->getMessage()]]);
            $order->note('اتصال به اسنپ‌پی ممکن نشد: '.$e->getMessage());
            session()->flash('status', 'اتصال به اسنپ‌پی ممکن نشد. چند لحظه بعد دوباره امتحان کنید یا روش دیگری انتخاب کنید.');

            return route('customer.orders.show', $order);
        }

        $payment->update(['meta' => ['payment_token' => $result['token']]]);

        return $result['url'];
    }
}
