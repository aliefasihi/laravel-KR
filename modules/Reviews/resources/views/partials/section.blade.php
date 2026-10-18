{{-- Hook «product.sections»: rating summary, reviews, form | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, PersianText};
    $reviews = \Modules\Reviews\Models\Review::query()->approved()->where('product_id', $product->id)->latest('id')->limit(50)->get();
    $count = $reviews->count();
    $avg = $count ? round($reviews->avg('rating'), 1) : 0;
    $dist = collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => $reviews->where('rating', $s)->count()]);
    $stars = fn (float $value, string $size = 'size-4') => collect(range(1, 5))->map(fn ($i) => '<svg class="'.$size.' '.($value >= $i - 0.25 ? 'text-caramel' : 'text-navy/15').'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.8l2.5 5.2 5.7.8-4.1 4 1 5.7L10 14.8l-5.1 2.7 1-5.7-4.1-4 5.7-.8L10 1.8Z"/></svg>')->implode('');
@endphp
<section id="reviews" class="glass rounded-[var(--radius-glass)] p-6 sm:p-10" aria-labelledby="reviews-title" x-data="{ more: false }">
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
            <livewire:reviews.form :product-id="$product->id" />
        </div>

        <div>
            @forelse ($reviews as $review)
                <article class="border-b border-line py-5 first:pt-0 last:border-0" @if ($loop->index >= 6) x-show="more" x-cloak @endif>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="flex">{!! $stars($review->rating) !!}</span>
                        @if ($review->title)<span class="font-bold text-navy">{{ $review->title }}</span>@endif
                    </div>
                    <p class="mt-2 text-sm leading-7 text-muted">{{ $review->body }}</p>
                    <p class="mt-2 flex flex-wrap items-center gap-2 text-xs text-subtle">
                        {{ $review->name }} · {{ Jalali::format($review->created_at) }}
                        @if ($review->is_verified_buyer)<span class="rounded-full bg-ok/15 px-2 py-0.5 font-bold text-ok">خریدار این محصول</span>@endif
                    </p>
                    @if ($review->reply)
                        <div class="mt-3 rounded-2xl bg-cream px-4 py-3 text-sm">
                            <p class="mb-1 text-xs font-bold text-rose">پاسخ خانه رمانتیک</p>
                            <p class="leading-7 text-navy">{{ $review->reply }}</p>
                        </div>
                    @endif
                </article>
            @empty
                <div class="py-8 text-center">
                    <p class="font-bold text-navy">هنوز نظری ثبت نشده است.</p>
                    <p class="mt-1 text-sm text-muted">اولین نفری باشید که تجربه‌اش را می‌نویسد.</p>
                </div>
            @endforelse
            @if ($count > 6)
                <button type="button" class="btn-glass mt-4" x-show="! more" @click="more = true">نمایش همه‌ی {{ PersianText::toPersianDigits($count) }} نظر</button>
            @endif
        </div>
    </div>
</section>
