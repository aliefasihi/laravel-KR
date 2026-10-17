{{-- Placement slot | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="placement placement--{{ str_replace('.', '-', $key) }} grid gap-4">
    @foreach ($banners as $banner)
        <a href="{{ $banner->link_url ?? '#' }}" class="block overflow-hidden rounded-[calc(var(--radius-tile)*1.5)]" @if ($banner->variant) data-variant="{{ $banner->variant }}" @endif>
            <picture>
                @if ($banner->mobileMedia)
                    <source media="(max-width: 767px)" srcset="{{ $banner->mobileMedia->url() }}">
                @endif
                @if ($banner->desktopMedia)
                    <img src="{{ $banner->desktopMedia->url() }}" alt="{{ $banner->alt }}" class="h-auto w-full"
                         width="{{ $banner->desktopMedia->width }}" height="{{ $banner->desktopMedia->height }}"
                         loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                @endif
            </picture>
        </a>
    @endforeach
</div>
