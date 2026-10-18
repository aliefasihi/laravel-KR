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
        <svg class="size-1/4 max-w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 19c-2.6-2-3.8-4.4-3.8-7.2S9.5 6.6 12 4.5c2.5 2.1 3.8 4.5 3.8 7.3S14.6 17 12 19Z"/>
            <path d="M12 19c-3.9-.3-6.8-2.2-8-5.6 2.4-1 4.6-.9 6.4.2M12 19c3.9-.3 6.8-2.2 8-5.6-2.4-1-4.6-.9-6.4.2"/>
        </svg>
    </div>
@endif
