{{-- Khaneh Romantic — storefront layout (RTL, glass) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($store = config('khaneh.store'))
<!DOCTYPE html>
<html lang="fa" dir="rtl" @if (config('khaneh.font') === 'kalameh') style="--font-brand: 'Kalameh'" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#23284f">
    <title>@yield('title', $store['tagline']) | {{ $store['name'] }}</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')">
    @endif
    <meta property="og:site_name" content="{{ $store['name'] }}">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:title" content="@yield('title', $store['name'])">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    @stack('head')
    @storefrontHook('head.end')

    <link rel="preload" href="/fonts/vazirmatn/vazirmatn-arabic-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    @if (config('khaneh.font') === 'kalameh')
        <link rel="stylesheet" href="/fonts/kalameh/kalameh.css">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Instant navigation: Chromium browsers prerender a page as soon as the visitor shows intent
         (hover on desktop, touch start on mobile). Cart/admin/new-tab links are left alone. --}}
    <script type="speculationrules">
    {"prerender": [{"where": {"and": [
        {"href_matches": "/*"},
        {"not": {"href_matches": "/cart"}},
        {"not": {"href_matches": "/admin/*"}},
        {"not": {"href_matches": "/livewire/*"}},
        {"not": {"selector_matches": "[target=_blank], [rel~=nofollow], [data-no-prerender]"}}
    ]}, "eagerness": "moderate"}]}
    </script>
</head>
<body @class(['flex min-h-dvh flex-col lg:pb-0', 'pb-24' => ! View::hasSection('buy_bar'), 'pb-44' => View::hasSection('buy_bar')])>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-[60] focus:rounded-full focus:bg-surface focus:px-5 focus:py-2.5 focus:shadow-lg">پرش به محتوا</a>

    @storefrontHook('body.start')
    <x-site.promo />
    <x-site.header />

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    <x-site.footer />
    <x-site.bottom-nav />
    <x-site.whatsapp :raised="View::hasSection('buy_bar')" />
    <x-ui.toast />

    @storefrontHook('body.end')
    @stack('scripts')
</body>
</html>
