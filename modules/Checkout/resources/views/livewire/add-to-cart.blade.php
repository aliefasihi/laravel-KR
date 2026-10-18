{{-- Variant picker + add to cart (glass buy box) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\PersianText; @endphp
<div class="space-y-5">
    @if ($this->variants->count() > 1)
        <fieldset>
            <legend class="mb-3 text-sm font-bold text-navy">انتخاب مدل</legend>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->variants as $variant)
                    @php($label = $variant->options ? implode(' · ', $variant->options) : $variant->sku)
                    <label @class([
                        'cursor-pointer rounded-2xl border px-4 py-2.5 text-sm font-medium transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-rose',
                        'border-navy bg-deep text-white shadow-[0_10px_20px_-12px_rgb(35_40_79/0.9)]' => $variantId === $variant->id,
                        'border-glint/80 bg-surface/70 text-navy hover:border-rose/40' => $variantId !== $variant->id && $variant->stock > 0,
                        'border-dashed border-navy/15 bg-transparent text-subtle line-through' => $variant->stock < 1,
                    ])>
                        <input type="radio" class="sr-only" wire:model.live="variantId" value="{{ $variant->id }}">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    <div class="flex items-end justify-between gap-4">
        <x-price :variant="$this->selected" size="lg" />
        @if ($this->selected && $this->selected->stock > 0 && $this->selected->stock <= 3)
            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose">فقط {{ PersianText::toPersianDigits($this->selected->stock) }} عدد باقی مانده</span>
        @endif
    </div>

    @if ($this->selected && $this->selected->stock > 0)
        <div class="flex items-stretch gap-3">
            <div class="flex items-center rounded-full border border-glint/80 bg-surface/80 p-1">
                <button type="button" wire:click="increment" class="grid size-10 place-items-center rounded-full text-lg font-bold text-navy hover:bg-cream" aria-label="افزایش تعداد">+</button>
                <span class="w-8 text-center text-base font-black text-navy" aria-live="polite">{{ PersianText::toPersianDigits($quantity) }}</span>
                <button type="button" wire:click="decrement" class="grid size-10 place-items-center rounded-full text-lg font-bold text-navy hover:bg-cream" aria-label="کاهش تعداد">−</button>
            </div>
            <button type="button" wire:click="add" wire:loading.attr="disabled" class="btn-navy flex-1 !py-3.5 text-[15px]">
                <svg wire:loading.remove wire:target="add" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Zm4 2V6a3 3 0 0 1 6 0v4"/></svg>
                <span wire:loading.remove wire:target="add">افزودن به سبد خرید</span>
                <span wire:loading wire:target="add">در حال افزودن…</span>
            </button>
        </div>
    @else
        <div class="flex items-center gap-3 rounded-2xl bg-navy/5 px-4 py-4 text-sm text-navy">
            <svg class="size-5 shrink-0 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01" stroke-linecap="round"/></svg>
            این کالا فعلاً موجود نیست؛ برای اطلاع از موجودی با ما در واتس‌اپ در تماس باشید.
        </div>
    @endif

    @if ($message)
        <p class="flex items-center gap-2 text-sm font-bold text-ok" role="status">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            {{ $message }}
        </p>
    @endif
</div>
