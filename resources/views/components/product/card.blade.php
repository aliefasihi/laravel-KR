{{-- Product card (glass) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['product', 'eager' => false])
@php
    ['variant' => $variant, 'single' => $single] = $product->cardSummary();
    $images = $product->media->take(2);
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
            <x-product.quick-add :variant="$variant" :single="$single" />
        </div>
    </div>
</article>
