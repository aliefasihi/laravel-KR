{{-- Block: short stats | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($items = array_values($data['items'] ?? []))
<div @class(['grid gap-4', 'sm:grid-cols-2' => count($items) === 2 || count($items) === 4, 'sm:grid-cols-3' => count($items) === 3 || count($items) >= 5])>
    @foreach ($items as $item)
        <div class="glass relative overflow-hidden rounded-[var(--radius-card)] px-6 pt-7 pb-6 text-center">
            <span class="absolute inset-x-10 top-0 h-1 rounded-b-full bg-gradient-to-l from-caramel to-rose" aria-hidden="true"></span>
            <p class="text-2xl font-black text-navy sm:text-3xl">{{ $item['value'] ?? '' }}</p>
            <p class="mt-2 text-sm leading-7 text-muted">{{ $item['label'] ?? '' }}</p>
        </div>
    @endforeach
</div>
