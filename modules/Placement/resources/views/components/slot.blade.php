{{-- Placement slot | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="placement placement--{{ str_replace('.', '-', $key) }}">
    @foreach ($banners as $banner)
        <a href="{{ $banner->link_url ?? '#' }}" @if ($banner->variant) data-variant="{{ $banner->variant }}" @endif>
            <picture>
                @if ($banner->mobileMedia)
                    <source media="(max-width: 767px)" srcset="{{ $banner->mobileMedia->url() }}">
                @endif
                @if ($banner->desktopMedia)
                    <img src="{{ $banner->desktopMedia->url() }}" alt="{{ $banner->alt }}"
                         width="{{ $banner->desktopMedia->width }}" height="{{ $banner->desktopMedia->height }}"
                         loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                @endif
            </picture>
        </a>
    @endforeach
</div>
