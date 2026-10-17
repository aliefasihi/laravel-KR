{{-- Responsive image from the Media module | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['media' => null, 'alt' => null, 'eager' => false, 'sizes' => '100vw'])

@if ($media)
    @php($variants = $media->variants ?? [])
    <picture>
        @isset($variants['avif'])
            <source type="image/avif" srcset="{{ \Illuminate\Support\Facades\Storage::disk($media->disk)->url($variants['avif']['path']) }}" sizes="{{ $sizes }}">
        @endisset
        <img src="{{ $media->url() }}"
             alt="{{ $alt ?? $media->alt ?? '' }}"
             @if ($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
             loading="{{ $eager ? 'eager' : 'lazy' }}"
             @if ($eager) fetchpriority="high" @endif
             decoding="async"
             {{ $attributes }}>
    </picture>
@else
    <div {{ $attributes->class(['flex items-center justify-center bg-mist text-glaze/40']) }} role="img" aria-label="{{ $alt ?? 'بدون تصویر' }}">
        <x-khatam class="size-1/3" />
    </div>
@endif
