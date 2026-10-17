{{-- Variant picker + add to cart | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\PersianText; @endphp
<div class="space-y-5">
    <x-price :variant="$this->selected" size="lg" />

    @if ($this->variants->count() > 1)
        <fieldset>
            <legend class="mb-2 text-sm font-bold">مدل</legend>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->variants as $variant)
                    @php($label = $variant->options ? implode(' · ', $variant->options) : $variant->sku)
                    <label @class([
                        'cursor-pointer rounded-xl border px-4 py-2 text-sm transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-glaze',
                        'border-glaze bg-glaze text-white' => $variantId === $variant->id,
                        'border-line bg-white hover:border-glaze' => $variantId !== $variant->id && $variant->stock > 0,
                        'border-line bg-white text-ink-soft/60 line-through' => $variant->stock < 1,
                    ])>
                        <input type="radio" class="sr-only" wire:model.live="variantId" value="{{ $variant->id }}">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    @if ($this->selected && $this->selected->stock > 0)
        <div class="flex items-stretch gap-3">
            <div class="flex items-center rounded-xl border border-line bg-white">
                <button type="button" wire:click="increment" class="px-3 py-3 text-lg" aria-label="افزایش تعداد">+</button>
                <span class="w-8 text-center font-bold" aria-live="polite">{{ PersianText::toPersianDigits($quantity) }}</span>
                <button type="button" wire:click="decrement" class="px-3 py-3 text-lg" aria-label="کاهش تعداد">−</button>
            </div>
            <button type="button" wire:click="add" wire:loading.attr="disabled"
                    class="flex-1 rounded-xl bg-glaze px-6 py-3 font-bold text-white transition hover:bg-glaze-deep disabled:opacity-60">
                <span wire:loading.remove wire:target="add">افزودن به سبد خرید</span>
                <span wire:loading wire:target="add">در حال افزودن…</span>
            </button>
        </div>
        @if ($this->selected->stock <= 3)
            <p class="text-sm text-anar">فقط {{ PersianText::toPersianDigits($this->selected->stock) }} عدد در انبار باقی مانده.</p>
        @endif
    @else
        <p class="rounded-xl bg-mist px-4 py-3 text-sm">این محصول فعلاً موجود نیست.</p>
    @endif

    @if ($message)
        <p class="text-sm text-glaze-deep" role="status">{{ $message }}</p>
    @endif
</div>
