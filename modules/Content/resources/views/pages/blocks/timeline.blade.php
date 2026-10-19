{{-- Block: timeline | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section class="glass rounded-[var(--radius-glass)] px-6 py-8 sm:px-12">
    @if (! empty($data['title']))<h2 class="mb-6 text-2xl font-black text-navy">{{ $data['title'] }}</h2>@endif
    <ol class="relative space-y-8 border-s-2 border-rose/20 ps-6">
        @foreach (array_values($data['items'] ?? []) as $item)
            <li class="relative">
                <span class="absolute -start-[1.95rem] top-1 size-3.5 rounded-full bg-rose ring-4 ring-rose-100"></span>
                <p class="text-sm font-black text-caramel-600">{{ $item['year'] ?? '' }}</p>
                <p class="mt-1 font-black text-navy">{{ $item['title'] ?? '' }}</p>
                @if (! empty($item['text']))<p class="mt-1.5 text-sm leading-7 text-muted">{{ $item['text'] }}</p>@endif
            </li>
        @endforeach
    </ol>
</section>
