<?php

/**
 * Khaneh Romantic — «Invite a friend»: personal codes, ?ref= links, rewards on the first purchase
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The link sets a 30-day cookie; when that visitor signs up the referrer is saved once (never
 * changed later, no self-referral). When the new customer's first qualifying order is completed,
 * both get a fixed wallet reward — the status change and both credits in one transaction. If that
 * order is refunded/cancelled, both rewards are taken back (as far as the balances allow).
 */

namespace Modules\Loyalty\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Loyalty\Models\Referral;
use Modules\Loyalty\Models\ReferralCode;
use Modules\Order\Models\Order;

final class Referrals
{
    public const COOKIE = 'kr_ref';

    public const COOKIE_DAYS = 30;

    public static function codeFor(int $customerId): string
    {
        if ($code = ReferralCode::query()->where('customer_id', $customerId)->value('code')) {
            return $code;
        }
        for ($i = 0; $i < 10; $i++) {
            $code = 'KR'.Str::upper(Str::random(5));
            if (preg_match('/[0O1I]/', $code) || ReferralCode::query()->where('code', $code)->exists()) {
                continue;
            }
            DB::table('loyalty_referral_codes')->insertOrIgnore(['customer_id' => $customerId, 'code' => $code, 'created_at' => now()]);

            return ReferralCode::query()->where('customer_id', $customerId)->value('code');
        }

        throw new \RuntimeException('Could not create a referral code.');
    }

    public static function link(int $customerId): string
    {
        return url('/').'?ref='.self::codeFor($customerId);
    }

    public static function normalize(?string $code): ?string
    {
        $code = Str::upper(trim((string) $code));

        return preg_match('/^[A-Z0-9]{4,16}$/', $code) ? $code : null;
    }

    /** A new customer arrived through a link: remember who invited them (once). */
    public static function attach(Customer $customer, ?string $code): ?Referral
    {
        $code = self::normalize($code);
        if (! $code || ! Loyalty::on('referral')) {
            return null;
        }
        $referrerId = ReferralCode::query()->where('code', $code)->value('customer_id');
        if (! $referrerId || $referrerId === $customer->id || Referral::query()->where('referred_id', $customer->id)->exists()) {
            return null;
        }
        DB::table('loyalty_referrals')->insertOrIgnore([
            'referrer_id' => $referrerId, 'referred_id' => $customer->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Referral::query()->where('referred_id', $customer->id)->first();
    }

    /** The referred customer's order was completed: reward both if it is the first qualifying one. */
    public static function rewardForOrder(Order $order): ?Referral
    {
        if (! $order->customer_id || ! Loyalty::on('referral') || ! Loyalty::on('wallet')) {
            return null;
        }
        $referral = Referral::query()->where('referred_id', $order->customer_id)->where('status', 'pending')->first();
        if (! $referral || Loyalty::orderBase($order) < Loyalty::rials('referral_min_order')) {
            return null;
        }
        $referrerReward = Loyalty::rials('referral_referrer_reward');
        $referredReward = Loyalty::rials('referral_referred_reward');

        return DB::transaction(function () use ($referral, $order, $referrerReward, $referredReward) {
            $claimed = Referral::query()->whereKey($referral->id)->where('status', 'pending')->update([
                'status' => 'rewarded', 'qualifying_order_id' => $order->id, 'rewarded_at' => now(),
                'referrer_reward' => $referrerReward, 'referred_reward' => $referredReward, 'updated_at' => now(),
            ]);
            if ($claimed !== 1) {
                return null;
            }
            $context = ['reference_type' => 'referral', 'reference_id' => $referral->id, 'order_id' => $order->id];
            if ($referrerReward > 0) {
                Wallet::credit($referral->referrer_id, $referrerReward, 'referral_reward', $context + ['description' => 'اولین خرید دوستی که معرفی کردید']);
            }
            if ($referredReward > 0) {
                Wallet::credit($referral->referred_id, $referredReward, 'referral_reward', $context + ['description' => 'هدیه‌ی عضویت با معرفی دوست']);
            }

            return $referral->fresh();
        });
    }

    /** The qualifying order was refunded/cancelled: take both rewards back. */
    public static function reverseForOrder(Order $order): ?Referral
    {
        $referral = Referral::query()->where('qualifying_order_id', $order->id)->where('status', 'rewarded')->first();
        if (! $referral) {
            return null;
        }

        return DB::transaction(function () use ($referral, $order) {
            if (Referral::query()->whereKey($referral->id)->where('status', 'rewarded')->update(['status' => 'reversed', 'updated_at' => now()]) !== 1) {
                return null;
            }
            $context = ['reference_type' => 'referral', 'reference_id' => $referral->id, 'order_id' => $order->id, 'description' => 'لغو/استرداد سفارش '.$order->number];
            Wallet::debitUpTo($referral->referrer_id, $referral->referrer_reward, 'referral_reversal', $context);
            Wallet::debitUpTo($referral->referred_id, $referral->referred_reward, 'referral_reversal', $context);

            return $referral->fresh();
        });
    }
}
