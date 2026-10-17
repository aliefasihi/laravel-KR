{{-- Khaneh Romantic — base layout (RTL) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    @stack('head')
</head>
<body>
    <main>
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
