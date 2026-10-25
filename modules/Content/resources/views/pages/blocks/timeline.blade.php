{{-- Block: timeline / steps | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section class="glass rounded-[var(--radius-glass)] px-6 py-8 sm:px-12 sm:py-11">
    @if (! empty($data['title']))<h2 class="mb-7 text-2xl font-black text-navy">{{ $data['title'] }}</h2>@endif
    <ol class="relative space-y-8 border-s-2 border-dashed border-caramel/40 ps-8">
        @foreach (array_values($data['items'] ?? []) as $item)
            <li class="relative">
                <span class="absolute -start-[3.05rem] top-0 grid min-w-9 place-items-center rounded-full bg-gradient-to-br from-rose to-caramel px-2 py-1.5 text-xs font-black text-white ring-4 ring-surface">{{ $item['year'] ?? '' }}</span>
                <p class="font-black text-navy">{{ $item['title'] ?? '' }}</p>
                @if (! empty($item['text']))<p class="mt-1.5 text-sm leading-7 text-muted">{{ $item['text'] }}</p>@endif
            </li>
        @endforeach
    </ol>
</section>
