<?php

/**
 * Khaneh Romantic — Back from Zarinpal: verify and settle the order
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Order\Support\OrderWorkflow;
use Modules\Payment\Methods\ZarinpalMethod;
use Modules\Payment\Models\Payment;

class ZarinpalController
{
    public function callback(Request $request, ZarinpalMethod $method, OrderWorkflow $workflow)
    {
        $payment = Payment::query()->where('method', 'zarinpal')->where('authority', (string) $request->query('Authority'))->first();
        abort_unless($payment, 404);
        $order = $payment->order;
        $back = fn (string $message, bool $placed = false) => redirect()->route('customer.orders.show', ['order' => $order, 'placed' => $placed ? 1 : null])->with('status', $message);

        if ($payment->status === 'paid') {
            return $back('پرداخت این سفارش قبلاً انجام شده است.', true);
        }
        if ($request->query('Status') !== 'OK') {
            $payment->update(['status' => 'cancelled']);
            $order->note('پرداخت آنلاین انجام نشد (انصراف یا خطای بانک).');

            return $back('پرداخت انجام نشد. اگر مبلغی از حساب شما کم شده، تا ۷۲ ساعت برمی‌گردد. می‌توانید دوباره پرداخت کنید.');
        }

        // one verify per authority, even if the shopper reloads the page
        $result = DB::transaction(function () use ($payment, $method) {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if ($locked->status !== 'pending') {
                return null;
            }

            return $method->client()->verify($locked->amount, (string) $locked->authority);
        });
        if ($result === null) {
            return $back('وضعیت پرداخت در حال بررسی است.');
        }

        if (! $result['ok']) {
            $payment->update(['status' => 'failed', 'meta' => $result['raw']]);
            $order->note('تأیید پرداخت زرین‌پال ناموفق بود: '.$result['error']);

            return $back('پرداخت تأیید نشد. اگر مبلغی از حساب شما کم شده، تا ۷۲ ساعت برمی‌گردد.');
        }

        $payment->update(['status' => 'paid', 'paid_at' => now(), 'reference' => $result['ref_id'], 'card_pan' => $result['card_pan'], 'meta' => $result['raw']]);
        $workflow->markPaid($order, 'پرداخت آنلاین زرین‌پال موفق — کد پیگیری '.$result['ref_id'].($result['card_pan'] ? ' — کارت '.$result['card_pan'] : '').'.');

        return $back('پرداخت با موفقیت انجام شد. کد پیگیری: '.$result['ref_id'], true);
    }
}
