{{-- product.buybox hook → wholesale price ladder for wholesale customers, «همکار هستید؟» for others | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Money, PersianText};
    use Modules\Wholesale\Support\Wholesale;
    $wholesale = app(Wholesale::class);
    $variant = $product->variants->where('is_active', true)->sortBy('price')->first();
    $ladder = $variant ? array_values(array_filter($wholesale->ladder($variant), fn ($t) => $t['price'])) : [];
@endphp
@if ($wholesale->current())
    @if ($ladder)
        <div class="mt-4 rounded-2xl bg-ok/[0.07] p-3 ring-1 ring-ok/20">
            <p class="text-sm font-black text-ok">قیمت همکار
                @if ($product->variants->where('is_active', true)->pluck('price')->unique()->count() > 1)
                    <span class="text-xs font-normal text-muted">(برای {{ $variant->options ? implode(' · ', $variant->options) : 'ارزان‌ترین مدل' }}؛ بقیه به همین نسبت)</span>
                @endif
            </p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($ladder as $tier)
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-muted">از {{ PersianText::toPersianDigits($tier['min_qty']) }} عدد <span class="text-xs">({{ Wholesale::percent($tier['percent']) }}٪)</span></span>
                        <span class="font-black text-navy">{{ Money::toman($tier['price']) }} <span class="text-xs font-medium text-muted">تومان</span></span>
                    </li>
                @endforeach
            </ul>
            @if (Wholesale::minimum())
                <p class="mt-2 text-xs text-subtle">با جمع خرید {{ Money::toman(Wholesale::minimum()) }} تومان به بالا، در سبد اعمال می‌شود.</p>
            @endif
        </div>
    @else
        <p class="mt-4 text-xs text-muted">{{ $wholesale->tiersFor($product) ? 'قیمت فعلی این محصول (حراج) از قیمت همکار هم پایین‌تر است.' : 'این محصول قیمت همکار ندارد.' }}</p>
    @endif
@elseif ($ladder && \App\Core\Settings\Settings::get('wholesale.teaser', true))
    <a href="{{ route('wholesale') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-muted transition hover:text-rose">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9.5 4.5 4h15L21 9.5M3 9.5h18M3 9.5a2.5 2.5 0 0 0 4.5 1.5 2.5 2.5 0 0 0 4.5 0 2.5 2.5 0 0 0 4.5 0A2.5 2.5 0 0 0 21 9.5M5 12v8h14v-8M10 20v-5h4v5"/></svg>
        مغازه‌دار یا همکار هستید؟ قیمت عمده
    </a>
@endif
