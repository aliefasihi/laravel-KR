<?php

/**
 * Khaneh Romantic — Back from SnappPay: verify, settle, settle the order
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * SnappPay posts {transactionId, state, amount} from the shopper's browser (a cross-site POST: no
 * session cookie, no CSRF token), so the work happens here and the shopper is then sent to a GET
 * page that shows the result with their own session.
 */

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Order\Support\OrderWorkflow;
use Modules\Payment\Methods\SnappPayMethod;
use Modules\Payment\Models\Payment;
use Throwable;

class SnappPayController
{
    public function callback(Request $request, SnappPayMethod $method, OrderWorkflow $workflow)
    {
        $payment = Payment::query()->where('method', 'snapppay')->where('authority', (string) $request->input('transactionId'))->first();
        abort_unless($payment, 404);
        $order = $payment->order;
        $done = fn (string $result) => redirect(URL::signedRoute('payment.snapppay.done', ['payment' => $payment, 'result' => $result]));

        if ($payment->status === 'paid') {
            return $done('ok');
        }
        $amount = (int) $request->input('amount');
        if (strtoupper((string) $request->input('state')) !== 'OK' || ! in_array($amount, [$payment->amount, $payment->amount + (int) $order->discount], true)) {
            $payment->update(['status' => 'cancelled']);
            $order->note('خرید اقساطی اسنپ‌پی انجام نشد (انصراف یا رد اعتبار).');

            return $done('cancel');
        }

        // one verify + settle per transaction, even if the callback arrives twice
        $token = DB::transaction(function () use ($payment) {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if ($locked->status !== 'pending') {
                return null;
            }
            $locked->update(['status' => 'review']);

            return $locked->meta['payment_token'] ?? null;
        });
        if ($token === null) {
            return $done('wait');
        }

        try {
            $method->client()->verify($token);
            $method->client()->settle($token);
        } catch (Throwable $e) {
            // the answer may have been lost on the way: ask for the status before giving up
            $status = rescue(fn () => $method->client()->status($token), '', report: false);
            if (! in_array($status, ['VERIFY', 'SETTLE'], true)) {
                $payment->update(['status' => 'failed', 'meta' => ($payment->meta ?? []) + ['error' => $e->getMessage()]]);
                $order->note('تأیید خرید اقساطی اسنپ‌پی ناموفق بود: '.$e->getMessage());

                return $done('fail');
            }
            if ($status === 'VERIFY') {
                try {
                    $method->client()->settle($token);
                } catch (Throwable) {
                    $order->note('اسنپ‌پی: پرداخت تأیید شد ولی تسویه هنوز انجام نشده؛ در پنل اسنپ‌پی بررسی کنید.');

                    return $done('wait');   // stays «review» in the transactions list
                }
            }
        }

        $payment->update(['status' => 'paid', 'paid_at' => now(), 'reference' => $token]);
        $workflow->markPaid($order, 'خرید اقساطی اسنپ‌پی تأیید و تسویه شد — شناسه‌ی تراکنش '.$payment->authority.'.');

        return $done('ok');
    }

    /** The shopper lands here (their own session again) and sees the result on the order page. */
    public function done(Request $request, Payment $payment)
    {
        $messages = [
            'ok' => 'خرید اقساطی با موفقیت انجام شد. اقساط را در اپ اسنپ‌پی می‌بینید.',
            'cancel' => 'خرید اقساطی انجام نشد. می‌توانید دوباره امتحان کنید یا روش دیگری انتخاب کنید.',
            'fail' => 'تأیید خرید اقساطی ممکن نشد. اگر مبلغی کم شده، اسنپ‌پی آن را برمی‌گرداند.',
            'wait' => 'وضعیت پرداخت در حال بررسی است.',
        ];
        $result = (string) $request->query('result');

        return redirect()->route('customer.orders.show', ['order' => $payment->order, 'placed' => $result === 'ok' ? 1 : null])
            ->with('status', $messages[$result] ?? $messages['wait']);
    }
}
