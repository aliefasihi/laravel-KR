<?php

/**
 * Khaneh Romantic — Login / sign-up with mobile + SMS code (one form for both)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Digits may be typed in any script (۰۹۱۲…, ٠٩١٢…, 0912…) and with spaces/dashes; an optional
 * «کد معرف» (invite code) is checked on the first step and used when the account is created.
 */

namespace Modules\Customer\Livewire;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Invites;
use Modules\Customer\Support\Mobile;
use Modules\Customer\Support\Otp;

class Login extends Component
{
    public string $step = 'mobile';   // mobile | code

    public string $mobile = '';

    public string $code = '';

    public string $invite = '';

    public bool $inviteOpen = false;

    #[Locked]
    public string $verifiedMobile = '';

    public int $retryIn = 0;

    public function mount(): void
    {
        $invites = app(Invites::class);
        if ($invites->enabled() && ($code = $invites->prefill())) {
            [$this->invite, $this->inviteOpen] = [$code, true];
        }
    }

    public function send(): void
    {
        $this->resetErrorBag();
        $mobile = Mobile::normalize($this->mobile);
        if (! $mobile) {
            $this->addError('mobile', 'شماره‌ی موبایل را درست وارد کنید؛ مثل ۰۹۱۲۱۲۳۴۵۶۷');

            return;
        }

        $invites = app(Invites::class);
        $this->invite = trim(PersianText::toLatinDigits($this->invite));
        if ($this->invite !== '' && $invites->enabled()) {
            $code = $invites->check($this->invite);
            if (! $code) {
                $this->inviteOpen = true;
                $this->addError('invite', 'این کد معرف پیدا نشد؛ دوباره بررسی کنید یا خالی بگذارید.');

                return;
            }
            $this->invite = $code;
        }

        $result = app(Otp::class)->send($mobile, request()->ip());
        $this->retryIn = $result['retry_in'];

        match ($result['status']) {
            Otp::SENT, Otp::WAIT => [$this->step, $this->verifiedMobile, $this->code] = ['code', $mobile, ''],
            Otp::LIMITED => $this->addError('mobile', 'تعداد درخواست‌ها زیاد بود. '.$this->minutes($result['retry_in']).' دیگر دوباره امتحان کنید.'),
            default => $this->addError('mobile', 'ارسال پیامک ممکن نشد. چند لحظه بعد دوباره امتحان کنید.'),
        };

        if ($this->step === 'code') {
            $this->dispatch('otp-sent', retryIn: $this->retryIn);
        }
    }

    public function resend(): void
    {
        $this->mobile = $this->verifiedMobile;
        $this->send();
    }

    public function verify(): void
    {
        $this->resetErrorBag();
        $code = preg_replace('/\D+/', '', PersianText::toLatinDigits($this->code));
        if (strlen($code) !== (int) config('modules.customer.otp.length')) {
            $this->addError('code', 'کد '.PersianText::toPersianDigits(config('modules.customer.otp.length')).' رقمی پیامک‌شده را وارد کنید.');

            return;
        }

        $status = app(Otp::class)->verify($this->verifiedMobile, $code);
        if ($status !== Otp::OK) {
            $this->addError('code', $status === Otp::WRONG ? 'کد اشتباه است.' : 'کد منقضی شده؛ دوباره کد بگیرید.');

            return;
        }

        $invites = app(Invites::class);
        $invite = $invites->enabled() ? (string) $invites->check($this->invite) : '';
        $customer = $invites->forSignup($invite, fn () => Customer::query()->firstOrCreate(['mobile' => $this->verifiedMobile]));
        $isNew = $customer->wasRecentlyCreated;
        if (! $isNew && $invite !== '') {
            $invites->apply($customer, $invite);   // a fresh account without orders may still add one
        }
        $customer->forceFill(['mobile_verified_at' => $customer->mobile_verified_at ?? now(), 'last_login_at' => now()])->save();

        Auth::guard('customer')->login($customer, remember: true);
        session()->regenerate();

        // new customers first complete their name; the cart / checkout they came from is kept as "intended"
        $this->redirectIntended(default: $isNew ? route('customer.account', ['welcome' => 1]) : route('customer.account'));
    }

    public function editMobile(): void
    {
        $this->step = 'mobile';
        $this->mobile = $this->verifiedMobile;
        $this->code = '';
        $this->resetErrorBag();
    }

    private function minutes(int $seconds): string
    {
        return PersianText::toPersianDigits((string) max(1, (int) ceil($seconds / 60))).' دقیقه';
    }

    public function render()
    {
        return view('customer::livewire.login', [
            'length' => (int) config('modules.customer.otp.length'),
            'invites' => app(Invites::class)->enabled(),
        ]);
    }
}
