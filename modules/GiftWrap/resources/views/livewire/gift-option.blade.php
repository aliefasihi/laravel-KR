{{-- Gift options in the cart / checkout summary | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="border-t border-line pt-4">
    <button type="button" wire:click="$toggle('open')" class="flex w-full items-center justify-between text-sm font-bold text-navy" :aria-expanded="$wire.open">
        <span class="flex items-center gap-2">
            <svg class="size-5 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M3 8h18v4H3zM12 21V8M12 8S10.5 3 8 4s0 4 4 4ZM12 8s1.5-5 4-4 0 4-4 4Z"/></svg>
            هدیه است؟
        </span>
        <svg @class(['size-4 transition', 'rotate-180' => $open]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
    </button>
    @if ($open)
        <div class="mt-3 space-y-3">
            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl bg-surface/60 px-4 py-3">
                <span class="text-sm text-navy">بسته‌بندی کادو
                    <span class="text-xs text-muted">{{ $fee > 0 ? '(+'.\App\Core\Support\Money::toman($fee).' تومان)' : '(رایگان)' }}</span>
                </span>
                <input type="checkbox" wire:model.live="wrap" class="size-[18px] accent-rose">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold text-navy">متن کارت پیام <span class="font-normal text-muted">(اختیاری، روی کارت چاپ می‌شود)</span></span>
                <textarea wire:model.blur="message" rows="2" maxlength="{{ $max }}" class="field !text-sm" placeholder="مثلاً: تولدت مبارک مریم جان! از طرف سارا"></textarea>
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-xs text-navy">
                <input type="checkbox" wire:model.live="hide_prices" class="size-4 accent-rose">
                فاکتور قیمت‌دار داخل بسته گذاشته نشود
            </label>
        </div>
    @endif
</div>
