<?php

/**
 * Khaneh Romantic — Everything that happens to an order after it is placed
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * One place for the side effects, used by the admin, the payment gateways and the scheduler:
 *  - every change leaves a note in the order's history
 *  - cancelled / failed / refunded orders give their stock back (and take it again if reopened)
 *  - events (order.paid, order.shipped…) drive SMS, coupon usage and the super-app outbox
 */

namespace Modules\Order\Support;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class OrderWorkflow
{
    public function __construct(private Stock $stock) {}

    public function changeStatus(Order $order, string $status, ?string $note = null, ?int $userId = null, bool $notify = true): Order
    {
        if (! array_key_exists($status, Order::STATUSES)) {
            throw new InvalidArgumentException("Unknown order status [{$status}]");
        }
        $from = $order->status;
        if ($from === $status) {
            return $order;
        }

        DB::transaction(function () use ($order, $status, $from, $note, $userId) {
            $order->status = $status;
            match ($status) {
                'shipped' => $order->shipped_at ??= now(),
                'completed' => $order->completed_at ??= now(),
                'cancelled', 'failed' => $order->cancelled_at = now(),
                default => null,
            };
            $order->save();

            $order->loadMissing('items');
            in_array($status, Order::HOLDS_STOCK, true)
                ? $this->stock->reduce($order)     // reopened: take the stock again
                : $this->stock->restore($order);   // cancelled, failed, refunded

            $order->note('وضعیت از «'.(Order::STATUSES[$from] ?? $from).'» به «'.Order::STATUSES[$status].'» تغییر کرد.'.($note ? ' '.$note : ''), 'system', $userId);
        });

        $payload = OrderEvents::payload($order, ['from' => $from, 'notify' => $notify]);
        Event::dispatch('order.status_changed', [$payload]);
        if (in_array($status, ['cancelled', 'failed'], true)) {
            Event::dispatch('order.cancelled', [$payload]);
        }
        if ($status === 'shipped') {
            Event::dispatch('order.shipped', [$payload]);
        }

        return $order;
    }

    /** Payment confirmed (gateway verified, card-to-card receipt approved, COD collected). */
    public function markPaid(Order $order, string $note, ?int $userId = null): Order
    {
        if ($order->isPaid()) {
            return $order;
        }

        $order->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
        $order->note($note, 'system', $userId);

        if (in_array($order->status, ['pending', 'on_hold', 'failed', 'cancelled'], true)) {
            $this->changeStatus($order, 'processing', null, $userId, notify: false);
        }

        Event::dispatch('order.paid', [OrderEvents::payload($order)]);

        return $order;
    }

    /** Receipt uploaded / payment to be checked by staff. */
    public function awaitPaymentReview(Order $order, string $note): Order
    {
        $order->forceFill(['payment_status' => 'pending_review'])->save();
        $order->note($note);
        if ($order->status === 'pending') {
            $this->changeStatus($order, 'on_hold', null, null, notify: false);
        }

        return $order;
    }

    public function ship(Order $order, ?string $trackingCode, ?int $userId = null): Order
    {
        $order->forceFill(['tracking_code' => $trackingCode ?: $order->tracking_code])->save();
        if ($order->status === 'shipped') {
            $order->note('کد رهگیری ثبت شد: '.$trackingCode, 'system', $userId);
            Event::dispatch('order.shipped', [OrderEvents::payload($order, ['notify' => true])]);

            return $order;
        }

        return $this->changeStatus($order, 'shipped', $trackingCode ? 'کد رهگیری: '.$trackingCode : null, $userId);
    }

    /**
     * Money back (all or part). Restocks the given quantities; the whole order becomes «مسترد شده»
     * when everything is refunded.
     *
     * @param  array<int, int>  $quantities  order item id => quantity to return to stock
     */
    public function refund(Order $order, int $amount, array $quantities = [], ?string $reason = null, ?int $userId = null, bool $toWallet = false): Order
    {
        $amount = max(0, min($amount, $order->total - $order->refunded_total));

        DB::transaction(function () use ($order, $amount, $quantities, $reason, $userId) {
            foreach ($quantities as $itemId => $qty) {
                $item = $order->items->firstWhere('id', (int) $itemId);
                $qty = $item ? min((int) $qty, $item->quantity - $item->refunded_quantity) : 0;
                if ($qty <= 0) {
                    continue;
                }
                OrderItem::query()->whereKey($item->id)->increment('refunded_quantity', $qty);
                if ($order->stock_reduced && $item->variant_id) {
                    $this->stock->restoreItem($item->variant_id, $qty);
                }
            }

            $order->refunded_total += $amount;
            $order->payment_status = $order->refunded_total >= $order->total ? 'refunded' : 'partially_refunded';
            $order->save();
            $order->note('استرداد '.Money::toman($amount).' تومان'.($reason ? ' — '.$reason : '').'.', 'system', $userId);
        });

        $order->refresh()->load('items');
        if ($order->payment_status === 'refunded' && $order->status !== 'refunded') {
            $this->changeStatus($order, 'refunded', null, $userId, notify: false);
        }
        Event::dispatch('order.refunded', [OrderEvents::payload($order, ['amount' => $amount, 'to_wallet' => $toWallet])]);

        return $order;
    }

    public function addNote(Order $order, string $body, string $type = 'private', ?int $userId = null): void
    {
        $order->note($body, $type, $userId);
        if ($type === 'customer') {
            Event::dispatch('order.customer_note', [OrderEvents::payload($order, ['note' => $body])]);
        }
    }

    /** Unpaid online orders older than the hold time give their stock back. */
    public function cancelUnpaid(): int
    {
        $minutes = (int) config('modules.order.hold_minutes');
        $count = 0;
        Order::query()->where('status', 'pending')->where('payment_status', 'unpaid')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->with('items')->each(function (Order $order) use ($minutes, &$count) {
                $this->changeStatus($order, 'cancelled', 'پرداخت در '.PersianText::toPersianDigits($minutes).' دقیقه انجام نشد.', notify: false);
                $count++;
            });

        return $count;
    }
}
