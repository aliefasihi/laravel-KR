{{-- Breadcrumbs + BreadcrumbList schema | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['items' => []]) {{-- ['label' => 'url', … ] last item may have null url --}}
@php
    $trail = array_merge(['خانه رمانتیک' => route('home')], $items);
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

<nav aria-label="مسیر صفحه" {{ $attributes->class(['text-sm text-ink-soft']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($trail as $label => $url)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)
                    <span class="text-line" aria-hidden="true">/</span>
                @endif
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-glaze">{{ $label }}</a>
                @else
                    <span aria-current="page" class="text-ink">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

@push('head')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
