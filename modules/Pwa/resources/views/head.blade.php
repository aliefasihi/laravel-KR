{{-- PWA: manifest, home-screen icon, service worker | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if (\Modules\Pwa\Http\Controllers\PwaController::enabled())
    @php($icons = \Modules\Pwa\Support\AppIcons::urls())
    <link rel="manifest" href="{{ route('pwa.manifest', absolute: false) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $icons['apple'] }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ \Modules\Pwa\Http\Controllers\PwaController::shortName() }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <script>
        addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); window.krInstallPrompt = e; dispatchEvent(new Event('kr-installable')); });
        if ('serviceWorker' in navigator) addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    </script>
@else
    {{-- switched off: let a worker installed earlier fetch the version that removes itself --}}
    <script>if ('serviceWorker' in navigator) navigator.serviceWorker.getRegistrations().then((list) => list.forEach((r) => r.update()));</script>
@endif
