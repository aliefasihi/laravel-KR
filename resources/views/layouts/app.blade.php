{{-- Khaneh Romantic — storefront layout (RTL) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0e7c7b">
    <title>@yield('title', 'خانه رمانتیک') | خانه رمانتیک</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')">
    @endif
    <meta property="og:site_name" content="خانه رمانتیک">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:title" content="@yield('title', 'خانه رمانتیک')">
    @stack('head')

    <link rel="preload" href="/fonts/vazirmatn/vazirmatn-arabic-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/fonts/fonts.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">پرش به محتوا</a>

    <x-site.header />

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    <x-site.footer />

    @stack('scripts')
</body>
</html>
