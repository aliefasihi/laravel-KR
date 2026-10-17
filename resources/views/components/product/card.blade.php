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

<article {{ $attributes->class(['group glass relative flex flex-col rounded-[var(--radius-card)] p-2.5 transition duration-300 hover:-translate-y-1 hover:bg-white/80 hover:shadow-[0_22px_44px_-24px_rgb(35_40_79/0.5)]']) }}>
    <div class="relative aspect-square overflow-hidden rounded-[1rem] bg-gradient-to-br from-white to-cream">
        <x-media.picture :media="$images->first()" :alt="$product->name" :eager="$eager"
                         sizes="(min-width: 1280px) 20vw, (min-width: 768px) 30vw, 50vw"
                         class="{{ $mainClass }}" />
        @if ($images->count() > 1)
            <x-media.picture :media="$images->last()" alt="" class="absolute inset-0 size-full scale-[1.04] object-cover opacity-0 transition duration-700 group-hover:scale-100 group-hover:opacity-100" />
        @endif

        <div class="absolute inset-x-2 top-2 flex items-start justify-between">
            @if ($variant && $variant->currentPrice() < $variant->price)
                <span class="badge-off">{{ \App\Core\Support\PersianText::toPersianDigits(\App\Core\Support\Money::discountPercent($variant->price, $variant->currentPrice())) }}٪</span>
            @else
                <span></span>
            @endif
            @unless ($variant)
                <span class="rounded-full bg-navy/80 px-2.5 py-1 text-[11px] font-bold text-white backdrop-blur">ناموجود</span>
            @endunless
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-1 px-1.5 pt-3 pb-1">
        <span class="text-[11px] font-bold text-caramel-600">{{ $product->brand->name_fa }}</span>
        <h3 class="line-clamp-2 min-h-11 text-[13.5px] leading-[1.6rem] font-bold text-navy">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0 after:rounded-[var(--radius-card)] focus:outline-none">{{ $product->name }}</a>
        </h3>
        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <x-price :variant="$variant" />
            @if ($single)
                <div class="relative z-10">
                    <livewire:checkout.quick-add :variant-id="$variant->id" :key="'qa-'.$product->id.'-'.$variant->id" />
                </div>
            @elseif ($variant)
                <span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-full bg-navy/5 text-navy transition group-hover:bg-navy group-hover:text-white" aria-hidden="true">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                </span>
            @endif
        </div>
    </div>
</article>
