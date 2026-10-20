<?php

/**
 * Khaneh Romantic — «باشگاه مشتریان» in the account: wallet, points (+ convert), level, invite
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Livewire;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Customer\Models\Customer;
use Modules\Loyalty\Models\PointsTransaction;
use Modules\Loyalty\Models\Referral;
use Modules\Loyalty\Models\WalletTransaction;
use Modules\Loyalty\Support\InsufficientBalance;
use Modules\Loyalty\Support\Levels;
use Modules\Loyalty\Support\Loyalty;
use Modules\Loyalty\Support\Points;
use Modules\Loyalty\Support\Referrals;
use Modules\Loyalty\Support\Wallet;

class Club extends Component
{
    #[Url(as: 'tab')]
    public string $history = 'wallet';

    public int $perPage = 10;

    public string $redeem = '';

    public string $inviteCode = '';

    private function customerId(): int
    {
        return (int) Auth::guard('customer')->id();
    }

    public function updatedHistory(): void
    {
        $this->history = in_array($this->history, ['wallet', 'points'], true) ? $this->history : 'wallet';
        $this->perPage = 10;
    }

    public function more(): void
    {
        $this->perPage += 20;
    }

    public function redeemAll(): void
    {
        $max = (int) Loyalty::get('points_max_redeem');
        $balance = max(0, Points::balance($this->customerId()));
        $this->redeem = (string) ($max ? min($max, $balance) : $balance);
    }

    public function convert(): void
    {
        abort_unless(Loyalty::on('points') && Loyalty::on('wallet'), 403);
        $this->resetErrorBag();
        $points = (int) PersianText::toLatinDigits(str_replace([',', '٬'], '', $this->redeem));
        $key = 'loyalty-redeem:'.$this->customerId();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('redeem', 'کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 600);

        try {
            $credit = Points::redeem($this->customerId(), $points);
        } catch (InsufficientBalance $e) {
            $this->addError('redeem', $e->getMessage());

            return;
        }
        $this->redeem = '';
        $this->history = 'wallet';
        $this->dispatch('toast', message: Money::toman($credit).' تومان به کیف پول شما اضافه شد.');
    }

    /** «کد معرف دارید؟» — a new member who skipped it on the sign-up form. */
    public function enterInvite(): void
    {
        $this->resetErrorBag();
        $customer = Auth::guard('customer')->user();
        $key = 'loyalty-invite:'.$customer->id;
        if (RateLimiter::tooManyAttempts($key, 8)) {
            $this->addError('inviteCode', 'کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 600);

        $code = Referrals::find($this->inviteCode);
        if (! $code || $code === Referrals::codeFor($customer->id)) {
            $this->addError('inviteCode', $code ? 'کد خودتان را نمی‌شود وارد کرد.' : 'این کد معرف پیدا نشد.');

            return;
        }
        if (! Referrals::enter($customer, $code)) {
            $this->addError('inviteCode', 'مهلت ثبت کد معرف برای حساب شما تمام شده است.');

            return;
        }
        $this->inviteCode = '';
        $this->dispatch('toast', message: 'کد معرف ثبت شد؛ هدیه بعد از اولین خریدتان می‌رسد.');
    }

    public function render()
    {
        $id = $this->customerId();
        $customer = Auth::guard('customer')->user();
        $spent = Levels::totalSpent($id);
        $levels = Loyalty::on('levels');
        $history = $this->history === 'points'
            ? PointsTransaction::query()->where('customer_id', $id)->latest('id')->limit($this->perPage + 1)->get()
            : WalletTransaction::query()->where('customer_id', $id)->latest('id')->limit($this->perPage + 1)->get();

        return view('loyalty::livewire.club', [
            'wallet' => Loyalty::on('wallet') ? Wallet::balance($id) : null,
            'points' => Loyalty::on('points') ? Points::balance($id) : null,
            'pointValue' => Loyalty::pointValue(),
            'earnPer' => Loyalty::earnPer(),
            'minRedeem' => (int) Loyalty::get('points_min_redeem'),
            'level' => $levels ? Levels::forSpent($spent) : null,
            'next' => $levels ? Levels::nextFor($spent) : null,
            'levels' => $levels ? Levels::all() : collect(),
            'spent' => $spent,
            'referral' => Loyalty::on('referral') ? $this->referral($customer) : null,
            'rows' => $history->take($this->perPage),
            'hasMore' => $history->count() > $this->perPage,
        ]);
    }

    private function referral(Customer $customer): array
    {
        $code = Referrals::codeFor($customer->id);
        $counts = Referral::query()->where('referrer_id', $customer->id)
            ->selectRaw('count(*) as invited, sum(case when status = ? then 1 else 0 end) as rewarded', ['rewarded'])->first();
        $invitedBy = Referral::query()->where('referred_id', $customer->id)->with('referrer')->first();

        return [
            'code' => $code,
            'link' => Referrals::linkFor($code),
            'invited' => (int) $counts?->invited,
            'rewarded' => (int) $counts?->rewarded,
            'referrer_reward' => Loyalty::rials('referral_referrer_reward'),
            'referred_reward' => Loyalty::rials('referral_referred_reward'),
            'min_order' => Loyalty::rials('referral_min_order'),
            'can_enter' => $invitedBy === null && Referrals::stillNew($customer),
            'invited_by' => $invitedBy,
        ];
    }
}
