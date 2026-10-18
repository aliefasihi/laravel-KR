{{-- Coupon field | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div>
    @if ($applied)
        <div class="flex items-center justify-between gap-3 rounded-2xl bg-rose-100 px-4 py-3 text-sm">
            <span class="flex items-center gap-2 font-bold text-rose">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7Z"/></svg>
                <span dir="ltr">{{ $applied }}</span>
            </span>
            <button type="button" wire:click="remove" class="text-xs font-bold text-muted hover:text-rose">حذف کد</button>
        </div>
    @else
        <button type="button" wire:click="$toggle('open')" class="flex w-full items-center justify-between text-sm font-bold text-navy" aria-expanded="{{ $open ? 'true' : 'false' }}">
            کد تخفیف دارید؟
            <svg @class(['size-4 transition', 'rotate-180' => $open]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
        </button>
        @if ($open || $errors->has('code'))
            <form wire:submit="apply" class="mt-3 flex gap-2">
                <label for="coupon-code" class="sr-only">کد تخفیف</label>
                <input id="coupon-code" type="text" wire:model="code" dir="ltr" autocomplete="off" placeholder="YALDA" class="field !rounded-full !py-2.5 uppercase">
                <button type="submit" class="btn-navy shrink-0 !px-5 !py-2.5" wire:loading.attr="disabled">اعمال</button>
            </form>
            @error('code')<p class="mt-2 text-xs text-rose">{{ $message }}</p>@enderror
        @endif
    @endif
</div>
