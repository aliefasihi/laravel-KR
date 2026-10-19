{{-- Block: features with icons | نویسنده: علی فصیحی (Ali Fasihi) --}}
<section class="space-y-4">
    @if (! empty($data['title']))
        <h2 class="px-2 text-2xl font-black text-navy">{{ $data['title'] }}</h2>
    @endif
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (array_values($data['items'] ?? []) as $item)
            <div class="glass flex gap-4 rounded-[var(--radius-card)] p-6">
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-rose-100 text-rose">
                    {!! rescue(fn () => svg('heroicon-o-'.($item['icon'] ?? 'sparkles'), 'size-6')->toHtml(), '', false) !!}
                </span>
                <div>
                    <p class="font-black text-navy">{{ $item['title'] ?? '' }}</p>
                    @if (! empty($item['text']))<p class="mt-1.5 text-sm leading-7 text-muted">{{ $item['text'] }}</p>@endif
                </div>
            </div>
        @endforeach
    </div>
</section>
