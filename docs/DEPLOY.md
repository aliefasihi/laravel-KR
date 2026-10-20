# چک‌لیست دیپلوی روی سرور
نویسنده: علی فصیحی (Ali Fasihi)

هر بار که روی سرور دیپلوی می‌کنی، این فایل را از بالا تا پایین برو. موردی که ✱ دارد هیچ‌وقت نباید جا بیفتد.

## ۱. اولین نصب (یک بار)

### ۱-۱. سرور
- PHP 8.3 با افزونه‌های `pdo_mysql`, `mbstring`, `intl`, `gd` یا `imagick`, `zip`, `bcmath`, `opcache`, `redis`
- Composer 2، Node 20+ (فقط برای build؛ روی سرور یا در CI)
- MariaDB 10.2+ یا MySQL 8+، Redis، و در صورت استفاده Meilisearch

### ۱-۲. پروژه
```bash
cd /srv/sites/khanehromantic
git clone <repo> .
cp .env.example .env            # و مقدارها را پر کن (بخش ۳)
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
npm ci && npm run build
php artisan migrate --force
chown -R www-data:www-data storage bootstrap/cache
```

### ۱-۳. ✱ کرون scheduler
بدون این خط، کارهای زمان‌دار انجام نمی‌شوند:
- لغو سفارش‌های پرداخت‌نشده و برگرداندن موجودی، هر ۵ دقیقه
- به‌روز کردن قیمت تخفیف‌های شروع یا تمام‌شده در جستجو، هر دقیقه
- پاک کردن کدهای ورود قدیمی، روزانه

```cron
* * * * * cd /srv/sites/khanehromantic && php artisan schedule:run >> /dev/null 2>&1
```
- با `crontab -e -u www-data` اضافه کن.
- در Docker، یک کانتینر جدا با همین دستور در حلقه بگذار.
- برای دیدن کارهای ثبت‌شده: `php artisan schedule:list`

### ۱-۴. ✱ صف (queue worker)
این‌ها در صف اجرا می‌شوند: ایندکس جستجو، نسخه‌های WebP تصاویر، و ایمپورت وردپرس.
```ini
; /etc/supervisor/conf.d/khaneh-worker.conf
[program:khaneh-worker]
command=php /srv/sites/khanehromantic/artisan queue:work --sleep=1 --tries=3 --max-time=3600
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=3600
```

### ۱-۵. وب‌سرور
تنظیم Caddy یا nginx و قوانین کش در `docs/PERFORMANCE.md` است.

## ۲. ✱ هر دیپلوی
```bash
cd /srv/sites/khanehromantic
php artisan down --render="errors::503" --retry=30   # اختیاری، برای migrationهای سنگین
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
composer run prod:optimize          # config/route/view/event cache + filament:optimize
php artisan queue:restart           # ✱ workerها کد تازه را بگیرند
php artisan up
```
- روی سرور با OPcache و `validate_timestamps=0`، بعد از دیپلوی `php-fpm` را reload کن.
- اگر ریسورس یا ماژول تازه‌ای در پنل دیده نشد: `composer run prod:clear` و بعد دوباره `prod:optimize`.

## ۳. `.env` برای production
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://khanehromantic.com
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
SEARCH_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=…
```
کلیدهای پیامک (`SMS_*`) و زرین‌پال (`ZARINPAL_*`) را می‌شود این‌جا یا در **پنل ← تنظیمات فروشگاه** گذاشت؛ مقدار پنل بر `.env` مقدم است.

## ۴. بعد از دیپلوی — آزمون دودی (۲ دقیقه)
- [ ] صفحه‌ی اصلی، یک دسته، یک محصول و جستجو باز می‌شوند.
- [ ] ورود ادمین و صفحه‌ی **ماژول‌ها**: ماژول‌های لازم روشن‌اند.
- [ ] `php artisan schedule:list`: کارها دیده می‌شوند.
- [ ] `php artisan search:status`: موتور در دسترس و تعداد سندها درست است.
- [ ] یک ورود پیامکی آزمایشی (کد می‌رسد).
- [ ] اگر پرداخت آنلاین فعال است: یک پرداخت آزمایشی در sandbox زرین‌پال.

## ۵. یک بار: انتقال از وردپرس
1. از phpMyAdmin سایت فعلی خروجی کامل دیتابیس بگیرید (`.sql.gz`).
2. پنل ← تنظیمات ← درون‌ریزی از وردپرس ← آپلود فایل ← تنظیمات تشخیص‌داده‌شده را نگاه کنید ← پیش‌نمایش ← اجرا.
   یا از ترمینال:
```bash
php artisan wp:import:load /path/khaneh_wp.sql.gz
php artisan wp:import --source=latest --dry-run     # لاگ را بخوانید
php artisan wp:import --source=latest
php artisan media:optimize --queue
php artisan filemanager:sync --move
php artisan search:rebuild
```
اگر پوشه‌ی `wp-content/uploads` روی همین سرور است `WP_UPLOADS_PATH` را بگذارید (سریع‌تر از دانلود تصاویر).
- [ ] ✱ `ORDER_NUMBER_OFFSET` را بالاتر از بزرگ‌ترین شماره‌ی سفارش ووکامرس بگذارید (مثلاً اگر آخرین سفارش ۴۸۲۱۰ است: `100000` کافی است) و `php artisan config:cache`.
- [ ] برگه‌هایی که در لاگ «معادل ندارد» آمده‌اند را در **سئو ← ریدایرکت‌ها** دستی بسازید.
- [ ] درون‌ریزی را می‌شود هر وقت دوباره اجرا کرد (مثلاً شب قبل از تعویض DNS)؛ فقط به‌روز می‌کند، تکراری نمی‌سازد.
