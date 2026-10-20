{{-- Product card — «مینیمال»: no frame, tall image, details underneath, add button on the image | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['product', 'eager' => false])
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    ['variant' => $variant, 'single' => $single] = $product->cardSummary();
    $images = $product->media->take(2);
    $sizes = '(min-width: 1280px) 22vw, (min-width: 768px) 30vw, 50vw';
@endphp

<article {{ $attributes->class(['group relative flex flex-col']) }}>
    <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-cream/70">
        <x-media.picture :media="$images->first()" :alt="$product->name" :eager="$eager" :sizes="$sizes"
                         :class="\Illuminate\Support\Arr::toCssClasses(['absolute inset-0 size-full object-cover transition duration-700', 'group-hover:opacity-0' => $images->count() > 1, 'group-hover:scale-[1.03]' => $images->count() < 2])" />
        @if ($images->count() > 1)
            <x-media.picture :media="$images->last()" alt="" :sizes="$sizes" class="absolute inset-0 size-full object-cover opacity-0 transition duration-700 group-hover:opacity-100" />
        @endif

        @if ($variant && $variant->currentPrice() < $variant->price)
            <span class="absolute top-3 start-3 rounded-full bg-rose px-2.5 py-1 text-[11px] font-black text-white">
                {{ PersianText::toPersianDigits(Money::discountPercent($variant->price, $variant->currentPrice())) }}٪-
            </span>
        @endif
        @unless ($variant)
            <span class="absolute inset-x-3 bottom-3 rounded-full bg-surface/90 py-1.5 text-center text-xs font-bold text-muted">ناموجود</span>
        @endunless

        <x-product.quick-add :variant="$variant" :single="$single"
                             class="!absolute bottom-3 end-3 !bg-surface !text-navy shadow-md transition hover:!bg-deep hover:!text-white lg:translate-y-2 lg:opacity-0 lg:group-hover:translate-y-0 lg:group-hover:opacity-100 lg:focus:translate-y-0 lg:focus:opacity-100" />
    </div>

    <div class="flex flex-1 flex-col gap-1 pt-3">
        <span class="text-[11px] font-medium tracking-wide text-subtle">{{ $product->brand->name_fa }}</span>
        <h3 class="line-clamp-2 text-[14px] leading-6 font-bold text-navy">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $product->name }}</a>
        </h3>
        <div class="mt-auto pt-1">
            @if ($variant)
                <span class="flex flex-wrap items-baseline gap-x-2">
                    <span @class(['text-[15px] font-black', 'text-rose' => $variant->currentPrice() < $variant->price, 'text-navy' => $variant->currentPrice() >= $variant->price])>
                        {{ Money::toman($variant->currentPrice()) }} <span class="text-[11px] font-medium text-muted">تومان</span>
                    </span>
                    @if ($variant->currentPrice() < $variant->price)
                        <del class="text-xs text-subtle">{{ Money::toman($variant->price) }}</del>
                    @endif
                </span>
            @else
                <span class="text-sm text-subtle">به‌زودی</span>
            @endif
        </div>
    </div>
</article>
