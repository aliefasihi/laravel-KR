{{-- Block: rich text | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section class="glass rounded-[var(--radius-glass)] px-6 py-8 sm:px-12 sm:py-11">
    @if (! empty($data['title']))
        <h2 class="mb-5 flex items-center gap-3 text-2xl font-black text-navy">
            <span class="h-7 w-1.5 shrink-0 rounded-full bg-gradient-to-b from-caramel to-rose" aria-hidden="true"></span>{{ $data['title'] }}
        </h2>
    @endif
    <div class="prose-fa max-w-3xl [&_li]:marker:text-caramel">{!! $data['content'] ?? '' !!}</div>
</section>
