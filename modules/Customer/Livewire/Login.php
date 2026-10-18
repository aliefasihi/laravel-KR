<?php

/**
 * Khaneh Romantic — Login / sign-up with mobile + SMS code (one form for both)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Livewire;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\Customer\Support\Otp;

class Login extends Component
{
    public string $step = 'mobile';   // mobile | code

    public string $mobile = '';

    public string $code = '';

    #[Locked]
    public string $verifiedMobile = '';

    public int $retryIn = 0;

    public function send(): void
    {
        $this->resetErrorBag();
        $mobile = Mobile::normalize($this->mobile);
        if (! $mobile) {
            $this->addError('mobile', 'شماره‌ی موبایل را درست وارد کنید؛ مثل ۰۹۱۲۱۲۳۴۵۶۷');

            return;
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

        $customer = Customer::query()->firstOrCreate(['mobile' => $this->verifiedMobile]);
        $isNew = $customer->wasRecentlyCreated;
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
        ]);
    }
}
