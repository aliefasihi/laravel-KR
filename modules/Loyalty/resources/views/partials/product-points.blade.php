{{-- Hook «product.buybox»: points this purchase earns | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\PersianText;
    use Modules\Loyalty\Support\{Levels, Loyalty};
    $best = $product->variants->where('is_active', true)->sortBy(fn ($v) => $v->currentPrice())->first();
    $customerId = auth('customer')->id();
    $points = $best ? Loyalty::pointsFor($best->currentPrice(), $customerId ? Levels::multiplier((int) $customerId) : 1.0) : 0;
@endphp
@if ($points > 0)
    <p class="mt-4 flex items-center gap-2 rounded-2xl bg-cream px-4 py-2.5 text-xs text-navy">
        <span class="text-base text-caramel-600" aria-hidden="true">★</span>
        با خرید این محصول <b>{{ Loyalty::fa((int) $points) }} امتیاز</b> باشگاه مشتریان می‌گیرید.
    </p>
@endif
