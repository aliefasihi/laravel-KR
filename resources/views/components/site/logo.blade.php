{{-- Brand logo: public/images/logo.webp if present, otherwise a lotus monogram + wordmark | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['light' => false])
@php
    $store = config('khaneh.store');
    $file = config('khaneh.logo');
    $hasImage = $file && file_exists(public_path($file));
@endphp

<a href="{{ route('home') }}" {{ $attributes->class(['flex shrink-0 items-center gap-2.5']) }} aria-label="{{ $store['name'] }} — صفحه اصلی">
    @if ($hasImage)
        <img src="{{ asset($file) }}" alt="{{ $store['name'] }}" class="h-11 w-auto sm:h-12" width="160" height="48">
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
