{{-- Breadcrumbs + BreadcrumbList schema | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['items' => []]) {{-- ['label' => 'url', … ] last item may have null url --}}
@php
    $trail = array_merge([config('khaneh.store.name') => route('home')], $items);
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($trail)->keys()->map(fn ($label, $i) => array_filter([
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $label,
            'item' => array_values($trail)[$i],
        ]))->all(),
    ];
@endphp

<nav aria-label="مسیر صفحه" {{ $attributes->class(['text-[13px]']) }}>
    <ol class="glass inline-flex max-w-full flex-wrap items-center gap-1 rounded-full px-4 py-2">
        @foreach ($trail as $label => $url)
            <li class="flex items-center gap-1">
                @if (! $loop->first)
                    <svg class="size-3 text-subtle" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                @endif
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="text-muted transition hover:text-rose">{{ $label }}</a>
                @else
                    <span aria-current="page" class="line-clamp-1 font-bold text-navy">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

@push('head')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
