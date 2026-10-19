{{-- Block: quote | نویسنده: علی فصیحی (Ali Fasihi) --}}
<figure class="glass-navy rounded-[var(--radius-glass)] px-6 py-10 text-center sm:px-14">
    <blockquote class="text-xl leading-10 font-bold">«{{ $data['text'] ?? '' }}»</blockquote>
    @if (! empty($data['author']))<figcaption class="mt-4 text-sm text-caramel">— {{ $data['author'] }}</figcaption>@endif
</figure>
