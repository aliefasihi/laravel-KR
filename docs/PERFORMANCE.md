# سرعت — چه کردیم و چه باید روی سرور روشن باشد
نویسنده: علی فصیحی (Ali Fasihi)

## داخل کد (انجام شده)

| مشکل | اندازه‌گیری قبل | بعد | راه‌حل |
|---|---|---|---|
| اسکرول صفحه‌ی اصلی (دسکتاپ، CPU ×۴ کند) | ۱۹ فریم، ۲۷ فریم طولانی | ۶۰ فریم، صفر | ۸۲ المان `backdrop-filter` به ۱۳ رسید. پس‌زمینه‌ی `fixed` و حباب blur متحرک حذف شدند. |
| کامپوننت Livewire در صفحه‌ی اصلی | ۳۳ | ۱ | دکمه‌ی افزودن کارت‌ها با Alpine کار می‌کند و فقط mini-cart آن را می‌گیرد. |
| کوئری صفحه‌ی اصلی | ۴۷ | ۶ (بدنه از کش) | `StoreCache` + کش HTML بدنه |
| زمان ساخت صفحه‌ی اصلی سمت سرور | ~۱۲۰ms | ~۲۰ms | همان |
| تصویر کارت محصول | فایل اصلی (۰٫۵ تا ۳ مگ) | WebP به اندازه‌ی لازم (~۳۰KB) | `media:optimize` و `srcset` |
| داشبورد پنل | ۶ کوئری COUNT هر ۵ ثانیه | بدون polling، کش ۵ دقیقه | `StoreOverview` |
| آواتار پنل | درخواست به ui-avatars.com در هر صفحه | SVG محلی | `InitialsAvatarProvider` |
| ناوبری پنل | بارگذاری کامل هر صفحه | SPA با prefetch | `->spa(hasPrefetching: true)` |
| جستجوی هدر | یک درخواست Livewire در هر حرف | JSON سبک، بدون session، کش در مرورگر | `/search/suggest` |
| رفتن به صفحه‌ی بعد | — | prerender با نشان دادن قصد کاربر (کروم/اندروید) | Speculation Rules در layout |

### کش صفحه‌ها (`App\Core\Support\StoreCache`)
- بدنه‌ی صفحه‌ی اصلی، منوی دسته‌ها، دسته‌های `/shop`، فهرست جستجو و آمار داشبورد کش می‌شوند.
- هر ذخیره یا حذف محصول، مدل، دسته، برند، مقاله، بنر یا تصویر **همه را با هم** باطل می‌کند (trait `FlushesStoreCache`).
- شروع یا پایان تخفیف و بنر هم زمان انقضا را جلو می‌آورد؛ قیمت قدیمی نمایش داده نمی‌شود.
- سقف عمر هر کش ۱۰ دقیقه است.

## روی سرور production (چک‌لیست)

چک‌لیست کامل نصب و دیپلوی، از جمله کرون scheduler و صف، در `docs/DEPLOY.md` است.

### `.env`
```dotenv
APP_ENV=production
APP_DEBUG=false          # با true، Livewire نسخه‌ی ۵۹۵KB جاوااسکریپت را می‌فرستد؛ با false نسخه‌ی min (~۱۵۰KB)
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
SEARCH_DRIVER=meilisearch
```

### بعد از هر دیپلوی
```bash
composer install --no-dev --optimize-autoloader   # Livewire assets را هم در public/vendor منتشر می‌کند
npm ci && npm run build
php artisan migrate --force
composer run prod:optimize        # config/route/view/event cache + filament:optimize (کامپوننت‌ها و آیکون‌ها)
php artisan queue:restart
```
اگر ریسورس Filament اضافه کردی و در پنل دیده نشد: `composer run prod:clear` و بعد دوباره `prod:optimize`.

### پروسه‌های همیشه‌روشن
```bash
php artisan queue:work --sleep=1 --tries=3 --max-time=3600   # supervisor یا یک کانتینر جدا
* * * * * cd /srv/sites/khanehromantic && php artisan schedule:run >> /dev/null 2>&1
```
- صف: ایندکس جستجو و نسخه‌های WebP تصاویر را می‌سازد.
- scheduler: هر دقیقه قیمت تخفیف‌های شروع‌شده یا تمام‌شده را در جستجو به‌روز می‌کند.

### PHP (`php.ini` یا `conf.d/opcache.ini`)
```ini
opcache.enable=1
opcache.memory_consumption=192
opcache.max_accelerated_files=30000
opcache.validate_timestamps=0     ; production: بعد از دیپلوی php-fpm را reload کن
opcache.jit=tracing
opcache.jit_buffer_size=64M
realpath_cache_size=4096K
realpath_cache_ttl=600
```

### Caddy (استک فعلی سرور)
```caddyfile
khanehromantic.com {
    root * /srv/sites/khanehromantic/public
    encode zstd gzip
    @immutable path /build/* /vendor/* /js/filament/* /css/filament/* /fonts/*
    header @immutable Cache-Control "public, max-age=31536000, immutable"
    @media path /storage/media/*
    header @media Cache-Control "public, max-age=2592000"
    php_fastcgi php-fpm:9000
    file_server
}
```
برای Apache همین قوانین در `public/.htaccess` هست. nginx هم معادل همین است: `gzip on` و `location ~ ^/(build|vendor|fonts)/ { expires 1y; add_header Cache-Control "public, immutable"; }`.

### تصاویر وردپرس بعد از ایمپورت
```bash
php artisan media:optimize --queue
php artisan search:rebuild
```

## لوکال (چرا روی سیستم خودت کند بود)
- **`php artisan serve` به‌طور پیش‌فرض یک درخواست در هر لحظه جواب می‌دهد.** CSS، فونت، تصاویر و Livewire پشت سر هم صف می‌کشند.
  - لینوکس یا مک: `PHP_CLI_SERVER_WORKERS=4` در `.env` (در `.env.example` هست).
  - ویندوز این را پشتیبانی نمی‌کند؛ از **Laravel Herd** یا **Laragon** (nginx + php-fpm) استفاده کن.
- **OPcache** را در php.ini لوکال روشن کن (`opcache.enable=1`). Filament بدون آن در هر درخواست صدها فایل را از نو کامپایل می‌کند.
- `SESSION_DRIVER=file` و `CACHE_STORE=file` برای لوکال از `database` سریع‌ترند.
- `APP_DEBUG=true` فقط موقع دیباگ؛ جاوااسکریپت Livewire را ۴ برابر سنگین‌تر می‌کند.

## اندازه‌گیری دوباره
- زمان سرور: `curl -s -o /dev/null -w "%{time_starttransfer}\n" https://khanehromantic.com/`
- مرورگر: Lighthouse در Chrome DevTools، حالت Mobile.
- تب Performance: هنگام اسکرول نباید فریم قرمز (Long frame) دیده شود.
