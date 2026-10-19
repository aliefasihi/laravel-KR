{{-- Block: call to action | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section class="relative overflow-hidden rounded-[var(--radius-glass)] bg-gradient-to-l from-cream via-rose-100 to-caramel-100 px-6 py-10 shadow-[var(--shadow-glass)] sm:px-12">
    <div class="relative flex flex-wrap items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-black text-navy">{{ $data['title'] ?? '' }}</h2>
            @if (! empty($data['text']))<p class="mt-2 leading-8 text-muted">{{ $data['text'] }}</p>@endif
        </div>
        @if (! empty($data['button_url']))
            <a href="{{ $data['button_url'] }}" class="btn-rose">{{ $data['button_label'] ?: 'ادامه' }}</a>
        @endif
    </div>
</section>
