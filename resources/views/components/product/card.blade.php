{{-- Product card (glass) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['product', 'eager' => false])
@php
    $active = $product->variants->where('is_active', true);
    $inStock = $active->where('stock', '>', 0);
    $variant = $inStock->sortBy(fn ($v) => $v->currentPrice())->first();
    $images = $product->media->take(2);
    $single = $active->count() === 1 && $variant;
    $mainClass = 'absolute inset-0 size-full object-cover transition duration-700 '
        .($images->count() > 1 ? 'group-hover:opacity-0' : 'group-hover:scale-[1.04]');
@endphp

<article {{ $attributes->class(['group glass relative flex flex-col rounded-[var(--radius-card)] p-2.5 transition duration-300 hover:-translate-y-1 hover:bg-surface/80 hover:shadow-[0_22px_44px_-24px_rgb(35_40_79/0.5)]']) }}>
    <div class="relative aspect-square overflow-hidden rounded-[1rem] bg-gradient-to-br from-surface to-cream">
        <x-media.picture :media="$images->first()" :alt="$product->name" :eager="$eager"
                         sizes="(min-width: 1280px) 20vw, (min-width: 768px) 30vw, 50vw"
                         class="{{ $mainClass }}" />
        @if ($images->count() > 1)
            <x-media.picture :media="$images->last()" alt="" sizes="(min-width: 1280px) 20vw, (min-width: 768px) 30vw, 50vw" class="absolute inset-0 size-full scale-[1.04] object-cover opacity-0 transition duration-700 group-hover:scale-100 group-hover:opacity-100" />
        @endif

        <div class="absolute inset-x-2 top-2 flex items-start justify-between">
            @if ($variant && $variant->currentPrice() < $variant->price)
                <span class="badge-off">{{ \App\Core\Support\PersianText::toPersianDigits(\App\Core\Support\Money::discountPercent($variant->price, $variant->currentPrice())) }}٪</span>
            @else
                <span></span>
            @endif
            @unless ($variant)
                <span class="rounded-full bg-deep/80 px-2.5 py-1 text-[11px] font-bold text-white backdrop-blur">ناموجود</span>
            @endunless
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-1 px-1.5 pt-3 pb-1">
        <span class="flex items-center justify-between gap-2 text-[11px] font-bold">
            <span class="text-caramel-600">{{ $product->brand->name_fa }}</span>
            @if (($product->rating_count ?? 0) > 0 && \App\Core\Modules\Modules::enabled('Reviews'))
                <span class="flex items-center gap-0.5 text-navy" title="امتیاز {{ number_format((float) $product->rating_avg, 1) }} از ۵">
                    <svg class="size-3.5 text-caramel" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.8l2.5 5.2 5.7.8-4.1 4 1 5.7L10 14.8l-5.1 2.7 1-5.7-4.1-4 5.7-.8L10 1.8Z"/></svg>
                    {{ \App\Core\Support\PersianText::toPersianDigits(number_format((float) $product->rating_avg, 1)) }}
                </span>
            @endif
        </span>
        <h3 class="line-clamp-2 min-h-11 text-[13.5px] leading-[1.6rem] font-bold text-navy">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0 after:rounded-[var(--radius-card)] focus:outline-none">{{ $product->name }}</a>
        </h3>
        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <x-price :variant="$variant" />
            @if ($single)
                {{-- One-tap add: plain Alpine → the header mini-cart handles it (no Livewire component per card). --}}
                <button type="button" x-data="{ busy: false, done: false }"
                        @click="busy = true; window.Livewire?.dispatch('cart-add', { variantId: {{ $variant->id }} })"
                        @cart-added.window="if ($event.detail.variantId === {{ $variant->id }}) { busy = false; done = $event.detail.added }"
                        :disabled="busy"
                        class="relative z-10 grid size-10 shrink-0 place-items-center rounded-full bg-deep text-white shadow-[0_8px_18px_-8px_rgb(35_40_79/0.9)] transition hover:bg-rose active:scale-95 disabled:opacity-60"
                        aria-label="افزودن به سبد خرید">
                    <svg x-show="!busy && !done" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Zm4 2V6a3 3 0 0 1 6 0v4M12 11v5M9.5 13.5h5"/></svg>
                    <svg x-show="!busy && done" x-cloak class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                    <svg x-show="busy" x-cloak class="size-[18px] animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                </button>
            @elseif ($variant)
                <span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-full bg-navy/5 text-navy transition group-hover:bg-deep group-hover:text-white" aria-hidden="true">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                </span>
            @endif
        </div>
    </div>
</article>
