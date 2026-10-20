{{-- Product card — «بازار»: compact store card: framed photo, two-line name, price aligned to the end with a red
     discount pill, a small add button; works stand-alone or inside a ruled grid | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['product', 'eager' => false])
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    ['variant' => $variant, 'single' => $single] = $product->cardSummary();
    $image = $product->media->first();
    $sale = $variant && $variant->currentPrice() < $variant->price;
@endphp

<article {{ $attributes->class(['group relative flex flex-col rounded-xl border border-line bg-surface p-3 transition hover:z-10 hover:shadow-[var(--shadow-float)]']) }}>
    <div class="relative aspect-square">
        <x-media.picture :media="$image" :alt="$product->name" :eager="$eager" sizes="(min-width: 1024px) 18vw, (min-width: 640px) 30vw, 45vw"
                         class="size-full rounded-lg object-contain" />
        @unless ($variant)
            <span class="absolute inset-x-0 bottom-0 rounded-md bg-cream/90 py-1 text-center text-[11px] font-bold text-muted">ناموجود</span>
        @endunless
    </div>

    <h3 class="mt-3 line-clamp-2 min-h-[2.6rem] text-[13px] leading-[1.3rem] text-navy">
        <a href="{{ $product->url() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $product->name }}</a>
    </h3>
    <span class="mt-1 text-[11px] text-subtle">{{ $product->brand->name_fa }}</span>

    <div class="mt-auto flex items-end justify-between gap-2 pt-3">
        <x-product.quick-add :variant="$variant" :single="$single" class="!size-8 !rounded-lg !shadow-none [&_svg]:!size-4" />
        @if ($variant)
            <span class="flex flex-col items-end">
                @if ($sale)
                    <span class="flex items-center gap-1.5">
                        <del class="text-[11px] text-subtle">{{ Money::toman($variant->price) }}</del>
                        <span class="rounded-full bg-rose px-1.5 py-0.5 text-[10px] font-black text-white">{{ PersianText::toPersianDigits(Money::discountPercent($variant->price, $variant->currentPrice())) }}٪</span>
                    </span>
                @endif
                <span class="text-[15px] font-black text-navy">{{ Money::toman($variant->currentPrice()) }} <span class="text-[10px] font-normal text-muted">تومان</span></span>
            </span>
        @else
            <span class="text-xs font-bold text-subtle">به‌زودی</span>
        @endif
    </div>
</article>
