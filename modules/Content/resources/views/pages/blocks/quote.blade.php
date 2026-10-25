{{-- Block: quote | نویسنده: علی فصیحی (Ali Fasihi) --}}
<figure class="glass-navy relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-12 text-center sm:px-16">
    <span class="pointer-events-none absolute -top-8 start-6 -z-10 font-serif text-[9rem] leading-none text-caramel/20" aria-hidden="true">”</span>
    <x-site.lotus :stroke="0.8" class="mx-auto mb-5 size-9 text-caramel" />
    <blockquote class="mx-auto max-w-3xl text-xl leading-10 font-bold sm:text-2xl sm:leading-[2.75rem]">«{{ $data['text'] ?? '' }}»</blockquote>
    @if (! empty($data['author']))<figcaption class="mt-5 text-sm font-bold text-caramel">— {{ $data['author'] }}</figcaption>@endif
</figure>
