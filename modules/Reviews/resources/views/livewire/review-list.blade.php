{{-- Product reviews list | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, PersianText};
    $stars = fn (float $value) => collect(range(1, 5))->map(fn ($i) => '<svg class="size-4 '.($value >= $i - 0.25 ? 'text-caramel' : 'text-navy/15').'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.8l2.5 5.2 5.7.8-4.1 4 1 5.7L10 14.8l-5.1 2.7 1-5.7-4.1-4 5.7-.8L10 1.8Z"/></svg>')->implode('');
@endphp
<div>
    @if ($total > 1)
        <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
            <span class="text-muted">مرتب‌سازی:</span>
            @foreach (\Modules\Reviews\Livewire\ReviewList::SORTS as $key => $label)
                <button type="button" wire:click="$set('sort', '{{ $key }}')"
                        @class(['rounded-full px-3 py-1.5 font-bold transition', 'bg-navy text-white' => $sort === $key, 'bg-surface/70 text-muted ring-1 ring-line hover:text-navy' => $sort !== $key])>{{ $label }}</button>
            @endforeach
        </div>
    @endif

    @forelse ($reviews as $review)
        <article wire:key="rv-{{ $review->id }}" class="border-b border-line py-5 first:pt-0 last:border-0">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span class="flex" aria-label="امتیاز {{ $review->rating }} از ۵">{!! $stars($review->rating) !!}</span>
                @if ($review->title)<span class="font-bold text-navy">{{ $review->title }}</span>@endif
            </div>
            <p class="mt-2 text-sm leading-7 whitespace-pre-line text-muted">{{ $review->body }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-subtle">
                {{ $review->name }} · {{ Jalali::format($review->created_at) }}
                @if ($review->is_verified_buyer)<span class="rounded-full bg-ok/15 px-2 py-0.5 font-bold text-ok">خریدار این محصول</span>@endif
                <button type="button" wire:click="helpful({{ $review->id }})" @disabled(in_array($review->id, $voted, true))
                        class="ms-auto inline-flex items-center gap-1 rounded-full px-2.5 py-1 ring-1 ring-line transition hover:text-navy disabled:bg-ok/10 disabled:text-ok disabled:ring-0">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M1 8.25a1.25 1.25 0 1 1 2.5 0v7.5a1.25 1.25 0 1 1-2.5 0v-7.5ZM11 3V1.7c0-.268.14-.526.395-.607A2 2 0 0 1 14 3c0 .995-.182 1.948-.514 2.826-.204.54.166 1.174.744 1.174h2.52c1.243 0 2.261 1.01 2.146 2.247a23.864 23.864 0 0 1-1.341 5.974C17.153 16.323 16.072 17 14.9 17h-3.192a3 3 0 0 1-1.341-.317l-2.734-1.366A3 3 0 0 0 6.292 15H5V8h.963c.685 0 1.258-.483 1.612-1.068a4.011 4.011 0 0 1 2.166-1.73c.432-.143.853-.386 1.011-.814.16-.432.248-.9.248-1.388Z"/></svg>
                    مفید بود @if ($review->helpful)({{ PersianText::toPersianDigits($review->helpful) }})@endif
                </button>
            </div>
            @if ($review->reply)
                <div class="mt-3 rounded-2xl bg-cream px-4 py-3 text-sm">
                    <p class="mb-1 text-xs font-bold text-rose">پاسخ {{ config('khaneh.store.name') }}</p>
                    <p class="leading-7 whitespace-pre-line text-navy">{{ $review->reply }}</p>
                </div>
            @endif
        </article>
    @empty
        <div class="py-8 text-center">
            <p class="font-bold text-navy">هنوز نظری ثبت نشده است.</p>
            <p class="mt-1 text-sm text-muted">اولین نفری باشید که تجربه‌اش را می‌نویسد.</p>
        </div>
    @endforelse

    @if ($total > $reviews->count())
        <button type="button" wire:click="loadMore" class="btn-glass mt-4">
            نظرهای بیشتر ({{ PersianText::toPersianDigits($total - $reviews->count()) }})
        </button>
    @endif
</div>
