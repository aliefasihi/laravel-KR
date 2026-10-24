{{-- OTP login: mobile → code | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\PersianText; @endphp
<div x-data="otpLogin(@js($retryIn), @js($length))" @otp-sent.window="start($event.detail.retryIn ?? $event.detail[0]?.retryIn)">
    @if ($step === 'mobile')
        <h1 class="text-2xl font-black text-navy sm:text-3xl">ورود یا ثبت‌نام</h1>
        <p class="mt-2 text-sm leading-7 text-muted">شماره‌ی موبایلتان را وارد کنید؛ یک کد برایتان پیامک می‌شود.</p>

        <form wire:submit="send" class="mt-8 space-y-5" novalidate>
            <div>
                <label for="otp-mobile" class="mb-2 block text-sm font-bold text-navy">شماره‌ی موبایل</label>
                <input id="otp-mobile" type="tel" inputmode="numeric" autocomplete="tel" dir="ltr" placeholder="09121234567" maxlength="20"
                       wire:model="mobile" autofocus
                       @class(['field !py-4 text-center text-lg tracking-[0.2em]', '!border-rose/50 !ring-4 !ring-rose/10' => $errors->has('mobile')])
                       @error('mobile') aria-invalid="true" aria-describedby="otp-mobile-error" @enderror>
                @error('mobile')<p id="otp-mobile-error" class="mt-2 text-sm text-rose">{{ $message }}</p>@enderror
            </div>
            @if ($invites)
                <div x-data="{ open: @js($inviteOpen) }">
                    <button type="button" x-show="! open" @click="open = true; $nextTick(() => $refs.invite.focus())" class="inline-flex items-center gap-1.5 text-sm font-bold text-rose hover:underline">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z"/></svg>
                        کد معرف دارید؟
                    </button>
                    <div x-show="open" x-cloak>
                        <label for="otp-invite" class="mb-2 flex items-center justify-between text-sm font-bold text-navy">
                            کد معرف <span class="text-xs font-medium text-subtle">اختیاری — فقط برای ثبت‌نام تازه</span>
                        </label>
                        <input id="otp-invite" x-ref="invite" type="text" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="20" placeholder="KR…"
                               wire:model="invite" data-latin
                               @class(['field text-center font-latin uppercase tracking-[0.25em]', '!border-rose/50 !ring-4 !ring-rose/10' => $errors->has('invite')])
                               @error('invite') aria-invalid="true" aria-describedby="otp-invite-error" @enderror>
                        @error('invite')<p id="otp-invite-error" class="mt-2 text-sm text-rose">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endif
            <button type="submit" class="btn-rose w-full !py-4" wire:loading.attr="disabled" wire:target="send">
                <span wire:loading.remove wire:target="send">دریافت کد</span>
                <span wire:loading wire:target="send">در حال ارسال…</span>
            </button>
        </form>
        <p class="mt-6 text-center text-xs leading-6 text-subtle">ورود شما یعنی پذیرش <a href="{{ route('pages.about') }}" class="text-rose underline underline-offset-4">قوانین {{ config('khaneh.store.name') }}</a>.</p>
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
                <input id="otp-code" type="text" inputmode="numeric" autocomplete="one-time-code" dir="ltr"
                       placeholder="{{ str_repeat('–', $length) }}" data-digits="{{ $length }}" wire:model="code" x-ref="code" autofocus
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

