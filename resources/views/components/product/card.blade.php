{{-- Product card | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['product', 'eager' => false])
@php
    $variant = $product->variants->where('is_active', true)->where('stock', '>', 0)->sortBy(fn ($v) => $v->currentPrice())->first();
    $cover = $product->media->first();
@endphp

<article {{ $attributes->class(['group relative flex flex-col']) }}>
    <div class="relative overflow-hidden rounded-[var(--radius-tile)] bg-white ring-1 ring-line/70">
        <x-media.picture :media="$cover" :alt="$product->name" :eager="$eager"
                         sizes="(min-width: 1024px) 25vw, 50vw"
                         class="aspect-square w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
        @unless ($variant)
            <span class="absolute start-3 top-3 rounded-full bg-ink/80 px-2.5 py-1 text-xs text-white">ناموجود</span>
        @endunless
    </div>
    <div class="mt-3 flex flex-1 flex-col gap-1.5 px-1">
        <span class="text-xs font-medium text-glaze">{{ $product->brand->name_fa }}</span>
        <h3 class="line-clamp-2 text-sm leading-6 font-medium text-ink">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        <x-price :variant="$variant" class="mt-auto pt-1" />
    </div>
</article>
