{{-- Contact form | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div>
    @if ($sentId !== null)
        <div class="rounded-[var(--radius-card)] bg-rose-100 p-6 text-center" role="status">
            <p class="text-lg font-black text-navy">پیام شما رسید 🌸</p>
            <p class="mt-2 text-sm leading-7 text-muted">
                همکاران ما به‌زودی با شما تماس می‌گیرند.
                @if ($sentId) شماره‌ی پیگیری: <span class="font-bold text-navy">{{ \App\Core\Support\PersianText::toPersianDigits($sentId) }}</span> @endif
            </p>
            <button type="button" wire:click="$set('sentId', null)" class="btn-glass mt-4">پیام دیگر</button>
        </div>
    @else
        <form wire:submit="send" class="grid gap-4 sm:grid-cols-2" novalidate>
            <div>
                <label for="cf-name" class="mb-1.5 block text-sm font-bold text-navy">نام و نام خانوادگی</label>
                <input id="cf-name" type="text" wire:model="name" autocomplete="name" class="field">
                @error('name')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="cf-mobile" class="mb-1.5 block text-sm font-bold text-navy">موبایل</label>
                <input id="cf-mobile" type="tel" inputmode="numeric" dir="ltr" wire:model="mobile" autocomplete="tel" placeholder="09xxxxxxxxx" class="field text-start">
                @error('mobile')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="cf-email" class="mb-1.5 block text-sm font-bold text-navy">ایمیل <span class="font-normal text-subtle">(اختیاری)</span></label>
                <input id="cf-email" type="email" dir="ltr" wire:model="email" autocomplete="email" class="field text-start">
                @error('email')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="cf-subject" class="mb-1.5 block text-sm font-bold text-navy">موضوع</label>
                <select id="cf-subject" wire:model.live="subject" class="field">
                    @foreach ($subjects as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            @if (str_contains($subject, 'سفارش'))
                <div class="sm:col-span-2">
                    <label for="cf-order" class="mb-1.5 block text-sm font-bold text-navy">شماره‌ی سفارش <span class="font-normal text-subtle">(اگر دارید)</span></label>
                    <input id="cf-order" type="text" inputmode="numeric" dir="ltr" wire:model="orderNumber" class="field text-start">
                </div>
            @endif
            <div class="sm:col-span-2">
                <label for="cf-message" class="mb-1.5 block text-sm font-bold text-navy">پیام</label>
                <textarea id="cf-message" rows="5" wire:model="message" maxlength="3000" class="field" placeholder="سؤال یا درخواست خود را بنویسید…"></textarea>
                @error('message')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            </div>
            <x-ui.honeypot />
            <div class="sm:col-span-2">
                <button type="submit" class="btn-rose" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="send">ارسال پیام</span>
                    <span wire:loading wire:target="send">در حال ارسال…</span>
                </button>
            </div>
        </form>
    @endif
</div>
