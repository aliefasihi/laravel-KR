{{-- Block: short stats | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($items = array_values($data['items'] ?? []))
<div @class(['grid gap-4', 'sm:grid-cols-2' => count($items) === 2 || count($items) === 4, 'sm:grid-cols-3' => count($items) === 3 || count($items) >= 5])>
    @foreach ($items as $item)
        <div class="glass rounded-[var(--radius-card)] p-6 text-center">
            <p class="text-2xl font-black text-rose">{{ $item['value'] ?? '' }}</p>
            <p class="mt-2 text-sm text-muted">{{ $item['label'] ?? '' }}</p>
        </div>
    @endforeach
</div>
