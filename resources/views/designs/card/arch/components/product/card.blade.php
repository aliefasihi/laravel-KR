{{-- Product card — «طاق»: the photo in an arch window, centred name and terracotta price, a small round
     add button resting on the arch | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['product', 'eager' => false])
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    ['variant' => $variant, 'single' => $single] = $product->cardSummary();
    $images = $product->media->take(2);
    $sizes = '(min-width: 1024px) 23vw, (min-width: 640px) 32vw, 58vw';
    $sale = $variant && $variant->currentPrice() < $variant->price;
@endphp

<article {{ $attributes->class(['group relative flex flex-col text-center']) }}>
    <div class="relative">
        <div class="arch-frame relative aspect-[3/4] bg-cream">
            <x-media.picture :media="$images->first()" :alt="$product->name" :eager="$eager" :sizes="$sizes"
                             :class="\Illuminate\Support\Arr::toCssClasses(['absolute inset-0 size-full object-cover transition duration-700', 'group-hover:opacity-0' => $images->count() > 1, 'group-hover:scale-105' => $images->count() < 2])" />
            @if ($images->count() > 1)
                <x-media.picture :media="$images->last()" alt="" :sizes="$sizes" class="absolute inset-0 size-full object-cover opacity-0 transition duration-700 group-hover:opacity-100" />
            @endif
            @unless ($variant)
                <span class="absolute inset-x-0 bottom-0 bg-surface/85 py-2 text-xs text-muted">ناموجود</span>
            @endunless
        </div>
        @if ($sale)
            <span class="absolute top-[18%] start-1 grid size-12 place-items-center rounded-full bg-rose text-xs font-bold text-white">
                {{ PersianText::toPersianDigits(Money::discountPercent($variant->price, $variant->currentPrice())) }}٪
            </span>
        @endif
        <x-product.quick-add :variant="$variant" :single="$single"
                             class="!absolute inset-x-0 -bottom-5 mx-auto !size-11 !bg-surface !text-deep !shadow-[0_10px_24px_-12px_rgb(63_74_50/0.7)] ring-1 ring-line hover:!bg-deep hover:!text-white" />
    </div>

    <div class="flex flex-1 flex-col items-center gap-1 px-1 pt-8">
        <span class="text-[11px] text-sage">{{ $product->brand->name_fa }}</span>
        <h3 class="line-clamp-2 text-[14px] leading-6 text-navy">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $product->name }}</a>
        </h3>
        <div class="mt-auto pt-1">
            @if ($variant)
                <span class="flex flex-wrap items-baseline justify-center gap-x-2">
                    <span class="text-[15px] font-bold text-rose">{{ Money::toman($variant->currentPrice()) }} <span class="text-[11px] font-normal text-muted">تومان</span></span>
                    @if ($sale)
                        <del class="text-xs text-subtle">{{ Money::toman($variant->price) }}</del>
                    @endif
                </span>
            @else
                <span class="text-sm text-subtle">به‌زودی</span>
            @endif
        </div>
    </div>
</article>
