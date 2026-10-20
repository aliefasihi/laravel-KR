{{-- Hook «product.sections»: rating summary, reviews, form | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, PersianText};
    $stats = \Modules\Reviews\Models\Review::query()->approved()->where('product_id', $product->id)
        ->selectRaw('rating, count(*) as c')->groupBy('rating')->pluck('c', 'rating');
    $count = (int) $stats->sum();
    $avg = $count ? round($stats->map(fn ($c, $r) => $c * $r)->sum() / $count, 1) : 0;
    $dist = collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => (int) ($stats[$s] ?? 0)]);
    $stars = fn (float $value, string $size = 'size-4') => collect(range(1, 5))->map(fn ($i) => '<svg class="'.$size.' '.($value >= $i - 0.25 ? 'text-caramel' : 'text-navy/15').'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.8l2.5 5.2 5.7.8-4.1 4 1 5.7L10 14.8l-5.1 2.7 1-5.7-4.1-4 5.7-.8L10 1.8Z"/></svg>')->implode('');
@endphp
<section id="reviews" class="glass rounded-[var(--radius-glass)] p-6 sm:p-10" aria-labelledby="reviews-title">
    <h2 id="reviews-title" class="mb-6 text-xl font-black text-navy">نظرات خریداران</h2>
    <div class="grid gap-8 lg:grid-cols-[17rem_1fr]">
        <div class="space-y-5">
            <div class="flex items-center gap-4">
                <span class="text-5xl font-black text-navy">{{ PersianText::toPersianDigits(number_format($avg, 1)) }}</span>
                <div>
                    <div class="flex" aria-label="امتیاز {{ $avg }} از ۵">{!! $stars($avg, 'size-5') !!}</div>
                    <p class="mt-1 text-xs text-muted">از {{ PersianText::toPersianDigits($count) }} نظر</p>
                </div>
            </div>
            <ul class="space-y-1.5 text-xs">
                @foreach ($dist as $star => $n)
                    <li class="flex items-center gap-2">
                        <span class="w-3 text-muted">{{ PersianText::toPersianDigits($star) }}</span>
                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-navy/10"><span class="block h-full rounded-full bg-caramel" style="width: {{ $count ? round($n * 100 / $count) : 0 }}%"></span></span>
                        <span class="w-6 text-end text-muted">{{ PersianText::toPersianDigits($n) }}</span>
                    </li>
                @endforeach
            </ul>
            <livewire:reviews.form :product-id="$product->id" :product-url="$product->url()" />
        </div>

        <div>
            <livewire:reviews.list :product-id="$product->id" :total="$count" :key="'reviews-'.$product->id" />
        </div>
    </div>
</section>
