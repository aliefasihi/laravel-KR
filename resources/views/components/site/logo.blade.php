{{-- Brand logo: the one uploaded in تنظیمات ← عمومی (a light one for dark backgrounds / dark mode),
     else public/images/logo.webp, else the lotus monogram + wordmark | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['light' => false, 'large' => false])
@php
    use App\Core\Settings\SiteSettings;
    $store = config('khaneh.store');
    $logo = SiteSettings::logoUrl();
    $logoLight = SiteSettings::logoUrl(light: true);
    if (! $logo && ($file = config('khaneh.logo')) && file_exists(public_path($file))) {
        $logo = asset($file);
    }
    // header: as tall as the shortest header allows (5rem in noir/arch); footer / login panel: larger
    $img = $large
        ? 'h-20 w-auto max-w-[16rem] object-contain sm:h-24 lg:h-28'
        : 'h-14 w-auto max-w-[12rem] object-contain sm:h-16 lg:h-[4.5rem]';
@endphp

<a href="{{ route('home') }}" {{ $attributes->class(['flex shrink-0 items-center gap-2.5']) }}>
    {{-- no aria-label: the accessible name must contain the visible wordmark (PageSpeed «label-content-name-mismatch») --}}
    @if ($light && ($logoLight || $logo))
        <img src="{{ $logoLight ?? $logo }}" alt="{{ $store['name'] }}" class="{{ $img }}" width="224" height="100">
    @elseif ($logo)
        <img src="{{ $logo }}" alt="{{ $store['name'] }}" @class([$img, 'dark:hidden' => $logoLight]) width="224" height="100">
        @if ($logoLight)
            <img src="{{ $logoLight }}" alt="{{ $store['name'] }}" class="{{ $img }} hidden dark:block" width="224" height="100">
        @endif
    @else
        <span @class(['grid size-11 place-items-center rounded-full shadow-[0_8px_20px_-10px_rgb(35_40_79/0.9)]', 'bg-deep text-caramel' => ! $light, 'bg-white/15 text-caramel' => $light])>
            <x-site.lotus class="size-6" />
        </span>
        <span class="leading-tight">
            <span @class(['block text-lg font-black', 'text-navy' => ! $light, 'text-white' => $light])>{{ $store['name'] }}</span>
            <span @class(['block font-latin text-[10px] tracking-[0.18em] uppercase', 'text-caramel-600' => ! $light, 'text-caramel' => $light]) dir="ltr" aria-hidden="true">{{ $store['name_en'] }}</span>
        </span>
    @endif
    <span class="sr-only"> — صفحه اصلی</span>
</a>
