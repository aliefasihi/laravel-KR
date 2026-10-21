{{-- Offline page (cached by the service worker; no outside CSS/JS) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ \App\Core\Support\Designs::themeColor() }}">
    <title>اتصال برقرار نیست | {{ $name }}</title>
    <link rel="stylesheet" href="/fonts/fonts.css">
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100dvh; display: grid; place-items: center; padding: 24px; font-family: Vazirmatn, Tahoma, sans-serif;
               background: #faf7f5; color: #23284f; text-align: center; }
        main { max-width: 360px; }
        img { width: 88px; height: 88px; border-radius: 22px; box-shadow: 0 10px 30px rgb(35 40 79 / .18); }
        h1 { font-size: 20px; margin: 22px 0 8px; }
        p { color: #66717f; line-height: 2; margin: 0 0 22px; font-size: 14px; }
        button { font: inherit; font-weight: 700; border: 0; border-radius: 999px; padding: 12px 30px; background: #cc3366; color: #fff; cursor: pointer; }
        @media (prefers-color-scheme: dark) { body { background: #101226; color: #f3f4f6; } p { color: #9ca3af; } }
    </style>
</head>
<body>
    <main>
        <img src="{{ $icon }}" alt="">
        <h1>اینترنت در دسترس نیست</h1>
        <p>به نظر می‌رسد اتصال گوشی یا رایانه‌تان قطع شده. وقتی دوباره وصل شدید، صفحه را تازه کنید؛ سبد خریدتان سر جایش است.</p>
        <button type="button" onclick="location.reload()">تلاش دوباره</button>
    </main>
    <script>addEventListener('online', () => location.reload());</script>
</body>
</html>
