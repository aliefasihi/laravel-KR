<?php

/**
 * Khaneh Romantic — Abandoned-cart reminders
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Only signed-in shoppers (checkout needs an account, so everyone who got as far as checkout is
 * one). Their cart is copied on every change; when it has sat untouched for the delay set in
 * تنظیمات ← سبد رهاشده (2 hours by default, at most 3 days old), they get ONE SMS with a link
 * that refills the cart on any device (and an optional coupon code). An order after the reminder
 * counts as «recovered» (with its amount) in the admin list; an order without one just clears it.
 * No SMS 21:00–09:00 — it waits for the morning run.
 */

namespace Modules\AbandonedCart\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AbandonedCart\Models\AbandonedCart;
use Modules\Sms\Support\Sms;
use Throwable;

final class Reminders
{
    public function remember(int $customerId, array $items, int $subtotal): void
    {
        $cart = AbandonedCart::query()->where('customer_id', $customerId)->first();

        if ($items === []) {
            // emptied: keep a reminded cart until the order (or a week) decides whether it came back
            if ($cart && ($cart->reminded_at === null || $cart->recovered_at !== null)) {
                $cart->delete();
            }

            return;
        }

        // an old round is over (recovered, or reminded long ago): this is a new cart
        if ($cart && ($cart->recovered_at !== null || $cart->reminded_at?->lt(now()->subDays(7)))) {
            $cart->delete();
            $cart = null;
        }

        AbandonedCart::query()->updateOrCreate(['customer_id' => $customerId], [
            'items' => $items,
            'subtotal' => $subtotal,
            'active_at' => now(),
        ] + ($cart ? [] : ['token' => Str::random(24)]));
    }

    /** An order was placed: after a reminder it's a recovered cart, otherwise there's nothing to remind. */
    public function ordered(array $order): void
    {
        if (empty($order['customer_id'])) {
            return;
        }
        $cart = AbandonedCart::query()->where('customer_id', $order['customer_id'])->first();
        if (! $cart) {
            return;
        }
        if ($cart->reminded_at && ! $cart->recovered_at && $cart->reminded_at->gt(now()->subDays(7))) {
            $cart->update(['recovered_at' => now(), 'order_id' => $order['id'] ?? null, 'order_total' => $order['total'] ?? null]);
        } elseif (! $cart->reminded_at) {
            $cart->delete();
        }
    }

    /** @return int reminders sent */
    public function sendDue(): int
    {
        $template = Settings::get('abandoned.template');
        if (! Settings::get('abandoned.enabled', true) || ! $template || ! Modules::enabled('Sms') || ! $this->awake()) {
            return 0;
        }

        $delay = max(15, (int) Settings::get('abandoned.delay_minutes', 120));
        $minimum = (int) Settings::get('abandoned.min_toman', 0) * 10;
        $sent = 0;

        AbandonedCart::query()->with('customer')
            ->whereNull('reminded_at')
            ->where('active_at', '<=', now()->subMinutes($delay))
            ->where('active_at', '>=', now()->subDays(3))
            ->where('subtotal', '>=', $minimum)
            ->limit(200)->get()
            ->each(function (AbandonedCart $cart) use ($template, &$sent) {
                $mobile = $cart->customer?->mobile;
                if (! $mobile) {
                    return;
                }
                $values = [
                    route('abandoned.restore', $cart->token),
                    (string) (Settings::get('abandoned.coupon_code') ?: '-'),
                    PersianText::toPersianDigits($cart->count()),
                    $cart->customer->first_name ?: 'مشتری عزیز',
                ];
                try {
                    app(Sms::class)->template($mobile, (string) $template, $values);
                    $cart->update(['reminded_at' => now()]);
                    $sent++;
                } catch (Throwable $e) {
                    Log::warning('Abandoned-cart SMS failed: '.$e->getMessage());
                }
            });

        return $sent;
    }

    private function awake(): bool
    {
        $hour = (int) now()->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'))->format('G');

        return $hour >= 9 && $hour < 21;
    }
}
