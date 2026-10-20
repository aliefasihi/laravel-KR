{{-- Product card — «شب طلایی»: the photo on an ivory print (white backgrounds melt into it), name and gold
     price underneath, square add button on hover | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['product', 'eager' => false])
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    ['variant' => $variant, 'single' => $single] = $product->cardSummary();
    $images = $product->media->take(2);
    $sizes = '(min-width: 1024px) 23vw, (min-width: 640px) 40vw, 70vw';
    $sale = $variant && $variant->currentPrice() < $variant->price;
@endphp

<article {{ $attributes->class(['group relative flex flex-col']) }}>
    <div class="relative aspect-[4/5] overflow-hidden bg-[#f4f1ea]">
        <x-media.picture :media="$images->first()" :alt="$product->name" :eager="$eager" :sizes="$sizes"
                         :class="\Illuminate\Support\Arr::toCssClasses(['absolute inset-0 size-full object-contain p-5 mix-blend-multiply transition duration-700', 'group-hover:opacity-0' => $images->count() > 1, 'group-hover:scale-105' => $images->count() < 2])" />
        @if ($images->count() > 1)
            <x-media.picture :media="$images->last()" alt="" :sizes="$sizes" class="absolute inset-0 size-full object-cover opacity-0 transition duration-700 group-hover:opacity-100" />
        @endif

        @if ($sale)
            <span class="absolute top-0 start-0 bg-black px-2.5 py-1.5 text-[11px] font-bold text-caramel">
                {{ PersianText::toPersianDigits(Money::discountPercent($variant->price, $variant->currentPrice())) }}٪ کمتر
            </span>
        @endif
        @unless ($variant)
            <span class="absolute inset-x-0 bottom-0 bg-black/80 py-2 text-center text-xs text-muted">ناموجود</span>
        @endunless

        <x-product.quick-add :variant="$variant" :single="$single"
                             class="!absolute bottom-3 end-3 !rounded-none !bg-black !text-caramel !shadow-none hover:!bg-caramel hover:!text-black lg:translate-y-3 lg:opacity-0 lg:group-hover:translate-y-0 lg:group-hover:opacity-100 lg:focus:translate-y-0 lg:focus:opacity-100" />
    </div>

    <div class="flex flex-1 flex-col items-center gap-1.5 pt-4 text-center">
        <span class="text-[10px] tracking-[0.25em] text-subtle">{{ $product->brand->name_fa }}</span>
        <h3 class="line-clamp-2 text-[14px] leading-6 text-navy">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $product->name }}</a>
        </h3>
        <div class="mt-auto pt-1">
            @if ($variant)
                <span class="flex flex-wrap items-baseline justify-center gap-x-2">
                    @if ($sale)
                        <del class="text-xs text-subtle">{{ Money::toman($variant->price) }}</del>
                    @endif
                    <span class="font-display text-xl text-caramel">{{ Money::toman($variant->currentPrice()) }}</span>
                    <span class="text-[11px] text-muted">تومان</span>
                </span>
            @else
                <span class="text-sm text-subtle">به‌زودی</span>
            @endif
        </div>
    </div>
</article>
