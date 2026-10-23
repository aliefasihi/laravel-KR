{{-- Bulk / corporate inquiry form | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div>
    @if ($sent)
        <p class="mt-5 rounded-2xl bg-ok/10 p-4 font-bold text-ok" role="status">درخواست شما رسید. به‌زودی با شما تماس می‌گیریم.</p>
    @else
        <form wire:submit="submit" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" novalidate>
            <x-ui.honeypot />
            <div>
                <label for="wi-name" class="mb-1.5 block font-bold text-navy">نام</label>
                <input id="wi-name" type="text" wire:model="name" class="field" maxlength="60">
                @error('name')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="wi-mobile" class="mb-1.5 block font-bold text-navy">موبایل</label>
                <input id="wi-mobile" type="tel" dir="ltr" inputmode="numeric" wire:model="mobile" class="field text-start" placeholder="09…">
                @error('mobile')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="wi-company" class="mb-1.5 block font-bold text-navy">شرکت / فروشگاه <span class="text-sm font-normal text-subtle">(اختیاری)</span></label>
                <input id="wi-company" type="text" wire:model="company" class="field" maxlength="80">
            </div>
            <div>
                <label for="wi-city" class="mb-1.5 block font-bold text-navy">شهر <span class="text-sm font-normal text-subtle">(اختیاری)</span></label>
                <input id="wi-city" type="text" wire:model="city" class="field" maxlength="40">
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <label for="wi-items" class="mb-1.5 block font-bold text-navy">چه کالاهایی و چه تعداد؟</label>
                <textarea id="wi-items" rows="3" wire:model="items" maxlength="2000" class="field" placeholder="مثلاً: ۵۰ عدد ماگ دوتایی برای هدیه‌ی پایان سال کارمندها، با بسته‌بندی کادو"></textarea>
                @error('items')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="wi-budget" class="mb-1.5 block font-bold text-navy">بودجه <span class="text-sm font-normal text-subtle">(اختیاری)</span></label>
                <input id="wi-budget" type="text" wire:model="budget" class="field" maxlength="60" placeholder="مثلاً: هر عدد تا ۵۰۰ هزار تومان">
            </div>
            <div>
                <label for="wi-date" class="mb-1.5 block font-bold text-navy">تا کی لازم دارید؟ <span class="text-sm font-normal text-subtle">(اختیاری)</span></label>
                <input id="wi-date" type="text" wire:model="date" class="field" maxlength="60" placeholder="مثلاً: نیمه‌ی اسفند">
            </div>
            <div class="flex items-end sm:col-span-2">
                <button type="submit" class="btn-navy" wire:loading.attr="disabled" wire:target="submit">فرستادن استعلام</button>
            </div>
        </form>
    @endif
</div>
