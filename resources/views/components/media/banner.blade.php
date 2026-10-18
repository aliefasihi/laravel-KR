{{-- Banner image: separate mobile/desktop art, each served as AVIF/WebP srcset when available | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['desktop' => null, 'mobile' => null, 'alt' => '', 'sizes' => '100vw', 'eager' => false])

@php($fallback = $desktop ?? $mobile)
@if ($fallback)
    <picture>
        @if ($mobile && $desktop)
            @foreach (['avif', 'webp'] as $format)
                @if ($set = $mobile->srcset($format))
                    <source media="(max-width: 767px)" type="image/{{ $format }}" srcset="{{ $set }}" sizes="100vw">
                @endif
            @endforeach
            <source media="(max-width: 767px)" srcset="{{ $mobile->url() }}">
        @endif
        @foreach (['avif', 'webp'] as $format)
            @if ($set = $fallback->srcset($format))
                <source type="image/{{ $format }}" srcset="{{ $set }}" sizes="{{ $sizes }}">
            @endif
        @endforeach
        <img src="{{ $fallback->url() }}" alt="{{ $alt }}"
             @if ($fallback->width) width="{{ $fallback->width }}" height="{{ $fallback->height }}" @endif
             loading="{{ $eager ? 'eager' : 'lazy' }}" @if ($eager) fetchpriority="high" @endif decoding="async"
             {{ $attributes }}>
    </picture>
@endif
