<?php

/**
 * Khaneh Romantic — Staff confirm or reject a payment (card-to-card receipt, cash on delivery)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Support;

use App\Core\Support\Money;
use Modules\Order\Models\Order;
use Modules\Order\Support\OrderWorkflow;
use Modules\Payment\Models\Payment;

class PaymentReview
{
    public function __construct(private OrderWorkflow $workflow) {}

    public function approve(Payment $payment, ?int $userId = null): void
    {
        $payment->update(['status' => 'paid', 'paid_at' => now(), 'reviewed_by' => $userId]);
        $this->workflow->markPaid($payment->order, 'پرداخت '.$this->label($payment).' به مبلغ '.Money::toman($payment->amount).' تومان تأیید شد.', $userId);
    }

    public function reject(Payment $payment, string $reason, ?int $userId = null): void
    {
        $payment->update(['status' => 'rejected', 'reviewed_by' => $userId]);
        $order = $payment->order;
        $order->forceFill(['payment_status' => 'unpaid'])->save();
        $this->workflow->addNote($order, 'رسید پرداخت تأیید نشد: '.$reason.' لطفاً رسید درست را دوباره بارگذاری کنید یا با ما تماس بگیرید.', 'customer', $userId);
    }

    /** Cash on delivery collected (or any payment received outside the site). */
    public function recordManual(Order $order, string $method, ?string $reference, ?int $userId = null): Payment
    {
        $payment = Payment::create([
            'order_id' => $order->id, 'method' => $method, 'amount' => $order->total, 'status' => 'paid',
            'reference' => $reference, 'paid_at' => now(), 'reviewed_by' => $userId,
        ]);
        $this->workflow->markPaid($order, 'دریافت وجه ثبت شد ('.$this->label($payment).($reference ? ' — '.$reference : '').').', $userId);

        return $payment;
    }

    private function label(Payment $payment): string
    {
        return match ($payment->method) {
            'card' => 'کارت به کارت',
            'cod' => 'در محل',
            'zarinpal' => 'آنلاین',
            default => $payment->method,
        };
    }
}
