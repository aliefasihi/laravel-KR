{{-- Product card — «پاپ»: ink frame with a flat shadow that lifts on hover, a tilted discount sticker,
     a full-width yellow «add» bar | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['product', 'eager' => false])
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    ['variant' => $variant, 'single' => $single] = $product->cardSummary();
    $image = $product->media->first();
    $sale = $variant && $variant->currentPrice() < $variant->price;
@endphp

<article {{ $attributes->class(['pop-frame pop-press group relative flex flex-col overflow-hidden rounded-2xl bg-surface']) }}>
    <div class="relative aspect-square overflow-hidden border-b-2 border-[var(--pop-ink)] bg-cream">
        <x-media.picture :media="$image" :alt="$product->name" :eager="$eager" sizes="(min-width: 1024px) 23vw, (min-width: 640px) 32vw, 62vw"
                         class="size-full object-cover transition duration-500 group-hover:scale-105 group-hover:rotate-1" />
        @if ($sale)
            <span class="absolute top-3 start-3 -rotate-6 rounded-lg border-2 border-[#141414] bg-caramel px-2 py-0.5 font-display text-lg text-[#141414] shadow-[2px_2px_0_#141414]">
                {{ PersianText::toPersianDigits(Money::discountPercent($variant->price, $variant->currentPrice())) }}٪-
            </span>
        @endif
        @unless ($variant)
            <span class="absolute end-3 top-3 rotate-3 rounded-lg border-2 border-[#141414] bg-white px-2 py-0.5 text-xs font-black text-[#141414]">تموم شد</span>
        @endunless
    </div>

    <div class="flex flex-1 flex-col gap-1 p-3">
        <span class="text-[11px] font-black text-rose">{{ $product->brand->name_fa }}</span>
        <h3 class="line-clamp-2 text-[14px] leading-6 font-bold text-navy">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $product->name }}</a>
        </h3>
        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            @if ($variant)
                <span class="flex flex-col">
                    @if ($sale)
                        <del class="text-[11px] text-subtle">{{ Money::toman($variant->price) }}</del>
                    @endif
                    <span class="font-display text-xl leading-none text-navy">{{ Money::toman($variant->currentPrice()) }} <span class="font-sans text-[10px] font-bold text-muted">تومان</span></span>
                </span>
            @else
                <span class="text-sm font-bold text-subtle">به‌زودی</span>
            @endif
            <x-product.quick-add :variant="$variant" :single="$single"
                                 class="!rounded-xl !border-2 !border-[var(--pop-ink)] !bg-caramel !text-[#141414] !shadow-none hover:!bg-[#141414] hover:!text-caramel" />
        </div>
    </div>
</article>
