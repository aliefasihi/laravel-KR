<?php

/**
 * Khaneh Romantic — Card-to-card: the shopper uploads the transfer receipt
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Livewire;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Order\Models\Order;
use Modules\Order\Support\OrderWorkflow;
use Modules\Payment\Models\Payment;

class ReceiptUpload extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $orderId;

    public $receipt = null;

    public string $reference = '';

    public string $note = '';

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function submit(OrderWorkflow $workflow): void
    {
        $order = Order::query()->whereKey($this->orderId)->where('customer_id', Auth::guard('customer')->id())->firstOrFail();
        abort_if($order->isPaid() || in_array($order->status, ['cancelled', 'failed', 'refunded'], true), 403);

        $this->reference = PersianText::toLatinDigits($this->reference);
        $this->validate([
            'receipt' => ['required', 'image', 'max:5120'],
            'reference' => ['required', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [], ['receipt' => 'تصویر رسید', 'reference' => 'شماره‌ی پیگیری']);

        $path = $this->receipt->store('payments/receipts/'.now()->format('Y/m'), 'local');
        Payment::create([
            'order_id' => $order->id, 'method' => 'card', 'amount' => $order->total, 'status' => 'review',
            'reference' => $this->reference, 'receipt_path' => $path, 'payer_note' => $this->note ?: null,
        ]);
        $workflow->awaitPaymentReview($order, 'رسید کارت به کارت بارگذاری شد (پیگیری '.$this->reference.').');

        $this->reset(['receipt', 'reference', 'note']);
        $this->redirectRoute('customer.orders.show', $order);
        session()->flash('status', 'رسید شما ثبت شد؛ پس از بررسی، سفارش آماده‌ی ارسال می‌شود.');
    }

    public function render()
    {
        return view('payment::livewire.receipt-upload');
    }
}
