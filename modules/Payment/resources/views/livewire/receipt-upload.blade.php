{{-- Card-to-card receipt form | نویسنده: علی فصیحی (Ali Fasihi) --}}
<form wire:submit="submit" class="mt-4 grid gap-3 sm:grid-cols-2" novalidate>
    <div class="sm:col-span-2">
        <label for="rc-file" class="mb-1.5 block text-sm font-bold text-navy">تصویر رسید</label>
        <input id="rc-file" type="file" accept="image/*" wire:model="receipt" class="field file:me-3 file:rounded-full file:border-0 file:bg-deep file:px-4 file:py-1.5 file:text-xs file:font-bold file:text-white">
        <div wire:loading wire:target="receipt" class="mt-1 text-xs text-muted">در حال بارگذاری…</div>
        @error('receipt')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="rc-ref" class="mb-1.5 block text-sm font-bold text-navy">شماره‌ی پیگیری / ارجاع</label>
        <input id="rc-ref" type="text" dir="ltr" inputmode="numeric" wire:model="reference" class="field">
        @error('reference')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="rc-note" class="mb-1.5 block text-sm font-bold text-navy">توضیح <span class="font-normal text-subtle">(اختیاری)</span></label>
        <input id="rc-note" type="text" wire:model="note" class="field" placeholder="مثلاً: ۴ رقم آخر کارت ۱۲۳۴">
    </div>
    <div class="sm:col-span-2">
        <button type="submit" class="btn-rose" wire:loading.attr="disabled">ثبت رسید پرداخت</button>
    </div>
</form>
