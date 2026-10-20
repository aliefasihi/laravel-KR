{{-- Responsive image from the Media module (soft placeholder when missing) | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Browsers pick the smallest AVIF/WebP version that fits `sizes`; the original is only a fallback. --}}
@props(['media' => null, 'alt' => null, 'eager' => false, 'sizes' => '100vw'])

@if ($media)
    <picture class="contents">
        @if ($avif = $media->srcset('avif'))
            <source type="image/avif" srcset="{{ $avif }}" sizes="{{ $sizes }}">
        @endif
        @if ($webp = $media->srcset('webp'))
            <source type="image/webp" srcset="{{ $webp }}" sizes="{{ $sizes }}">
        @endif
        <img src="{{ $media->url() }}"
             alt="{{ $alt ?? $media->alt ?? '' }}"
             @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
             loading="{{ $eager ? 'eager' : 'lazy' }}"
             @if ($eager) fetchpriority="high" @endif
             decoding="async"
             {{ $attributes }}>
    </picture>
@else
    <div {{ $attributes->class(['flex items-center justify-center bg-gradient-to-br from-cream via-surface to-rose-100 text-caramel/60']) }} role="img" aria-label="{{ $alt ?? 'بدون تصویر' }}">
        <x-site.lotus class="size-1/4 max-w-16" stroke="1.2" />
    </div>
@endif
