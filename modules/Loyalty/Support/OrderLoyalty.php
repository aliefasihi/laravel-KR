<?php

/**
 * Khaneh Romantic — What orders do to the club: pay with the wallet, earn points, count for the
 * level, reward a referral — and undo it all on refund / cancellation
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Order of work on «completed» (fixed): the points use the level the customer had BEFORE this
 * order, then the order counts towards the level, then the referral reward.
 */

namespace Modules\Loyalty\Support;

use App\Core\Support\Money;
use Illuminate\Support\Facades\DB;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Order\Models\Order;
use Modules\Order\Support\OrderException;

final class OrderLoyalty
{
    /** Inside the order transaction: take the wallet credit the quote used. */
    public static function placing(Order $order, Quote $quote): void
    {
        $amount = (int) ($quote->meta['wallet'] ?? 0);
        if ($amount <= 0) {
            return;
        }
        try {
            Wallet::debit((int) $order->customer_id, $amount, 'order_payment', ['order_id' => $order->id, 'description' => 'سفارش '.$order->number]);
        } catch (InsufficientBalance) {
            throw new OrderException('موجودی کیف پول شما تغییر کرده است؛ صفحه را تازه کنید.', 'total');
        }
        $order->forceFill(['wallet_used' => $amount])->save();
        $order->note('پرداخت '.Money::toman($amount).' تومان از کیف پول.');
    }

    /** @param  array<string, mixed>  $payload  OrderEvents::payload + from */
    public static function statusChanged(array $payload): void
    {
        $order = Order::query()->find($payload['id']);
        if (! $order) {
            return;
        }
        if ($payload['status'] === 'completed') {
            self::completed($order);
        } elseif (in_array($payload['status'], ['refunded', 'cancelled', 'failed'], true)) {
            self::undone($order);
        }
    }

    public static function completed(Order $order): void
    {
        if (! $order->customer_id) {
            return;
        }
        if (Loyalty::on('points')) {
            $multiplier = Levels::multiplier((int) $order->customer_id);   // level before this order
            $points = Loyalty::pointsFor(Loyalty::orderBase($order), $multiplier);
            if (Points::earnForOrder($order, $points, $multiplier)) {
                $order->note(Loyalty::fa((int) $points).' امتیاز باشگاه به مشتری داده شد.');
                Notifier::points((int) $order->customer_id, $points);
            }
        }
        if (Loyalty::on('levels')) {
            Levels::addOrder($order);
        }
        if ($referral = Referrals::rewardForOrder($order)) {
            $order->note('جایزه‌ی معرفی پرداخت شد.');
            Notifier::referral($referral);
        }
    }

    public static function undone(Order $order): void
    {
        self::returnWallet($order, null);
        if (Points::reverseOrder($order)) {
            $order->note('امتیاز این سفارش برگشت خورد.');
        }
        Levels::removeOrder($order);
        if (Referrals::reverseForOrder($order)) {
            $order->note('جایزه‌ی معرفی برگشت خورد.');
        }
    }

    /**
     * Money refunded: the wallet's share comes back in proportion (all of it on a full refund);
     * «to wallet» refunds the cash part as store credit too.
     *
     * @param  array<string, mixed>  $payload  OrderEvents::payload + amount, to_wallet
     */
    public static function refunded(array $payload): void
    {
        $order = Order::query()->find($payload['id']);
        if (! $order || ! $order->customer_id) {
            return;
        }
        $amount = (int) ($payload['amount'] ?? 0);
        if (! empty($payload['to_wallet']) && $amount > 0 && Loyalty::on('wallet')) {
            Wallet::credit((int) $order->customer_id, $amount, 'refund_to_wallet', ['order_id' => $order->id, 'description' => 'استرداد سفارش '.$order->number]);
            $order->note('مبلغ '.Money::toman($amount).' تومان به کیف پول مشتری برگشت.');
        }
        $share = $order->payment_status === 'refunded' || $order->total <= 0
            ? null                                                   // everything left
            : (int) floor($order->wallet_used * $amount / max(1, $order->total));
        self::returnWallet($order, $share);
    }

    /** Give back (part of) what was paid from the wallet — never more than was taken. */
    private static function returnWallet(Order $order, ?int $amount): void
    {
        DB::transaction(function () use ($order, $amount) {
            $row = Order::query()->whereKey($order->id)->lockForUpdate()->first(['wallet_used', 'wallet_refunded']);
            $left = (int) $row->wallet_used - (int) $row->wallet_refunded;
            $give = $amount === null ? $left : min($left, $amount);
            if ($give <= 0 || ! $order->customer_id) {
                return;
            }
            Order::query()->whereKey($order->id)->increment('wallet_refunded', $give);
            Wallet::credit((int) $order->customer_id, $give, 'order_refund', ['order_id' => $order->id, 'description' => 'سفارش '.$order->number]);
            $order->note(Money::toman($give).' تومان به کیف پول مشتری برگشت.');
        });
    }
}
