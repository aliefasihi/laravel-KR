{{-- Block: rich text | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section class="glass rounded-[var(--radius-glass)] px-6 py-8 sm:px-12 sm:py-10">
    @if (! empty($data['title']))
        <h2 class="mb-5 text-2xl font-black text-navy">{{ $data['title'] }}</h2>
    @endif
    <div class="prose-fa">{!! $data['content'] ?? '' !!}</div>
</section>
