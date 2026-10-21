{{-- «or 4 instalments with SnappPay» under the buy box (only for prices SnappPay can take) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $price = $product->variants->where('is_active', true)->where('stock', '>', 0)->map(fn ($v) => $v->currentPrice())->min();
@endphp
@if ($price && \Modules\Payment\Methods\SnappPayMethod::inRange($price))
    <p class="mt-4 flex items-center gap-2 rounded-2xl bg-cream/70 px-4 py-3 text-xs text-navy">
        <svg class="size-5 shrink-0 text-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
        <span>یا <b>۴ قسط</b> {{ \App\Core\Support\Money::toman(intdiv($price, 4)) }} تومانی با <b>اسنپ‌پی</b> — در صفحه‌ی پرداخت انتخاب کنید.</span>
    </p>
@endif
