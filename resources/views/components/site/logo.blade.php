{{-- Brand logo: the one uploaded in تنظیمات ← عمومی (a light one for dark backgrounds / dark mode),
     else public/images/logo.webp, else the lotus monogram + wordmark | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['light' => false])
@php
    use App\Core\Settings\SiteSettings;
    $store = config('khaneh.store');
    $logo = SiteSettings::logoUrl();
    $logoLight = SiteSettings::logoUrl(light: true);
    if (! $logo && ($file = config('khaneh.logo')) && file_exists(public_path($file))) {
        $logo = asset($file);
    }
    $img = 'h-11 w-auto max-w-[11rem] object-contain sm:h-12';
@endphp

<a href="{{ route('home') }}" {{ $attributes->class(['flex shrink-0 items-center gap-2.5']) }} aria-label="{{ $store['name'] }} — صفحه اصلی">
    @if ($light && ($logoLight || $logo))
        <img src="{{ $logoLight ?? $logo }}" alt="{{ $store['name'] }}" class="{{ $img }}" width="160" height="48">
    @elseif ($logo)
        <img src="{{ $logo }}" alt="{{ $store['name'] }}" @class([$img, 'dark:hidden' => $logoLight]) width="160" height="48">
        @if ($logoLight)
            <img src="{{ $logoLight }}" alt="{{ $store['name'] }}" class="{{ $img }} hidden dark:block" width="160" height="48">
        @endif
    @else
        <span @class(['grid size-11 place-items-center rounded-full shadow-[0_8px_20px_-10px_rgb(35_40_79/0.9)]', 'bg-deep text-caramel' => ! $light, 'bg-white/15 text-caramel' => $light])>
            <x-site.lotus class="size-6" />
        </span>
        <span class="leading-tight">
            <span @class(['block text-lg font-black', 'text-navy' => ! $light, 'text-white' => $light])>{{ $store['name'] }}</span>
            <span @class(['block font-latin text-[10px] tracking-[0.18em] uppercase', 'text-caramel-600' => ! $light, 'text-caramel' => $light]) dir="ltr">{{ $store['name_en'] }}</span>
        </span>
    @endif
</a>
