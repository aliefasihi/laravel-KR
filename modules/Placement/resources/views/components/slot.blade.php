{{-- Placement slot | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div {{ $attributes->class(['placement placement--'.str_replace('.', '-', $key), 'grid gap-4']) }}>
    @foreach ($banners as $banner)
        <a href="{{ $banner->link_url ?? '#' }}" class="block overflow-hidden rounded-[var(--radius-glass)]" @if ($banner->variant) data-variant="{{ $banner->variant }}" @endif>
            <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt" :eager="$loop->first" class="h-auto w-full" />
        </a>
    @endforeach
</div>
