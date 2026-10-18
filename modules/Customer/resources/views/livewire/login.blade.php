{{-- OTP login: mobile → code | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\PersianText; @endphp
<div x-data="otpLogin(@js($retryIn), @js($length))" @otp-sent.window="start($event.detail.retryIn ?? $event.detail[0]?.retryIn)">
    @if ($step === 'mobile')
        <h1 class="text-2xl font-black text-navy sm:text-3xl">ورود یا ثبت‌نام</h1>
        <p class="mt-2 text-sm leading-7 text-muted">شماره‌ی موبایلتان را وارد کنید؛ یک کد برایتان پیامک می‌شود.</p>

        <form wire:submit="send" class="mt-8 space-y-5" novalidate>
            <div>
                <label for="otp-mobile" class="mb-2 block text-sm font-bold text-navy">شماره‌ی موبایل</label>
                <input id="otp-mobile" type="tel" inputmode="numeric" autocomplete="tel" dir="ltr" placeholder="09121234567" maxlength="14"
                       wire:model="mobile" autofocus
                       @class(['field !py-4 text-center text-lg tracking-[0.2em]', '!border-rose/50 !ring-4 !ring-rose/10' => $errors->has('mobile')])
                       @error('mobile') aria-invalid="true" aria-describedby="otp-mobile-error" @enderror>
                @error('mobile')<p id="otp-mobile-error" class="mt-2 text-sm text-rose">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-rose w-full !py-4" wire:loading.attr="disabled" wire:target="send">
                <span wire:loading.remove wire:target="send">دریافت کد</span>
                <span wire:loading wire:target="send">در حال ارسال…</span>
            </button>
        </form>
        <p class="mt-6 text-center text-xs leading-6 text-subtle">ورود شما یعنی پذیرش <a href="{{ route('pages.about') }}" class="text-rose hover:underline">قوانین خانه رمانتیک</a>.</p>
    @else
        <button type="button" wire:click="editMobile" class="mb-6 inline-flex items-center gap-1.5 text-sm font-bold text-muted hover:text-rose">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg>
            ویرایش شماره
        </button>
        <h1 class="text-2xl font-black text-navy sm:text-3xl">کد تأیید را وارد کنید</h1>
        <p class="mt-2 text-sm leading-7 text-muted">
            کد {{ PersianText::toPersianDigits($length) }} رقمی به شماره‌ی
            <span class="font-bold text-navy" dir="ltr">{{ PersianText::toPersianDigits($verifiedMobile) }}</span> پیامک شد.
        </p>

        <form wire:submit="verify" class="mt-8 space-y-5" novalidate x-ref="form">
            <div>
                <label for="otp-code" class="sr-only">کد تأیید</label>
                <input id="otp-code" type="text" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="{{ $length + 2 }}"
                       placeholder="{{ str_repeat('–', $length) }}" wire:model="code" x-ref="code" autofocus
                       @input="autoSubmit($event.target)"
                       @class(['field !py-4 text-center font-latin text-3xl font-black tracking-[0.6em] placeholder:tracking-[0.6em]', '!border-rose/50 !ring-4 !ring-rose/10' => $errors->has('code')])
                       @error('code') aria-invalid="true" aria-describedby="otp-code-error" @enderror>
                @error('code')<p id="otp-code-error" class="mt-2 text-sm text-rose">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-rose w-full !py-4" wire:loading.attr="disabled" wire:target="verify">
                <span wire:loading.remove wire:target="verify">ورود</span>
                <span wire:loading wire:target="verify">در حال بررسی…</span>
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <span x-show="left > 0" class="text-muted">ارسال دوباره تا <span class="font-bold text-navy" x-text="toFa(clock)"></span></span>
            <button type="button" x-show="left <= 0" x-cloak wire:click="resend" class="font-bold text-rose hover:underline">ارسال دوباره‌ی کد</button>
        </div>
    @endif
</div>

