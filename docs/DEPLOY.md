# چک‌لیست دیپلوی روی سرور
نویسنده: علی فصیحی (Ali Fasihi)

راه‌اندازی کامل سرور پارس وی دی اس (IR_VPS_03) قدم‌به‌قدم: پوشه‌ی `deploy/` و «راهنمای راه‌اندازی سرور خانه رمانتیک» (PDF). روی آن سرور به‌جای دستورهای بخش ۲ از `sudo /srv/khanehromantic/bin/deploy.sh` استفاده کن.

هر بار که روی سرور دیپلوی می‌کنی، این فایل را از بالا تا پایین برو. موردی که ✱ دارد هیچ‌وقت نباید جا بیفتد.

## ✱ تغییرات نسبت به PDF (نسخه‌ی ۱)
این سه جای راهنمای PDF عوض شده است؛ بقیه‌ی راهنما همان است.

**بخش ۴، «گرفتن فایل‌های تنظیم»: مخزن خصوصی است.** `git clone` با https از سرور کار نمی‌کند؛ همان راه دوم PDF را بروید: `server-kit.zip` تازه را (ساخته‌شده از همین پوشه‌ی `deploy/`) با `scp` بفرستید و در `/opt/khaneh-kit` باز کنید.

**بخش ۱۳، پیش از «اولین دیپلوی»: کلید خواندن مخزن.** `deploy.sh` کد را با SSH از گیت‌هاب می‌گیرد (پورت 443 از `ssh.github.com`):
```bash
/srv/khanehromantic/bin/github-key.sh      # یک کلید عمومی چاپ می‌کند
```
آن را در گیت‌هاب بگذارید: مخزن ← Settings ← Deploy keys ← Add deploy key (تیک Allow write access خاموش). دوباره همان دستور را بزنید تا «✔ سرور به مخزن گیت‌هاب دسترسی دارد» بگوید. اگر گیت‌هاب از سرور در دسترس نبود، «دیپلوی بدون گیت‌هاب» بخش ۱۷.

**بخش ۱۴: به‌جای درون‌ریزی از وردپرس، داده‌های لپ‌تاپ.** همه‌ی داده‌ها (محصولات، مشتری‌ها، سفارش‌ها، تنظیمات، تصاویر) از ۱۴۰۵/۰۸ در دیتابیس لوکال وارد می‌شوند و وردپرس دیگر منبع نیست:
```bash
# روی مک — اول همه‌چیز push و روی سرور دیپلوی شده باشد
bash deploy/scripts/pack-local-data.sh            # /tmp/khaneh-data.tar.gz
scp /tmp/khaneh-data.tar.gz kr:/tmp/ && rm /tmp/khaneh-data.tar.gz
# روی سرور
/srv/khanehromantic/bin/import-local-data.sh /tmp/khaneh-data.tar.gz
```
- داده‌های سرور با داده‌های لپ‌تاپ جایگزین می‌شود (قبلش بکاپ می‌گیرد). شب تعویض دامنه همین را دوباره بزنید.
- ✱ بسته مشخصات مشتری‌ها و `APP_KEY` لپ‌تاپ را دارد تا کلیدهای پنل (پیامک، ربات…) با `APP_KEY` تازه‌ی سرور دوباره رمز شوند و از دست نروند. فقط با `scp`؛ هرگز در چت یا ایمیل. اسکریپت سرور بعد از کار پاکش می‌کند.
- `APP_KEY` سرور همان کلید تازه‌ی بخش ۱۳ است و بعد از این عوض نمی‌شود.
- `ORDER_NUMBER_OFFSET` همان ۱۰۰۰۰۰ بماند.
- حساب‌های مدیر لپ‌تاپ هم می‌آیند؛ رمزشان باید حداقل ۱۲ حرف باشد (`php artisan khaneh:admin` رمز تازه نمی‌دهد؛ از پنل ← کاربران عوض کنید).
- تصاویر برند (`khaneh:brand-images`) لازم نیست: همراه داده‌ها می‌آیند.

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
php artisan key:generate        # ✱ کلید تازه‌ی همین سرور؛ هرگز APP_KEY لوکال را کپی نکن
php artisan push:keys           # ✱ کلیدهای نوتیفیکیشن همین سرور (خروجی را در .env بگذار)
chmod 600 .env
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
SESSION_SECURE_COOKIE=true
ZARINPAL_SANDBOX=false
```
کلیدهای پیامک (`SMS_*`) و زرین‌پال (`ZARINPAL_*`) را می‌شود این‌جا یا در **پنل ← تنظیمات فروشگاه** گذاشت؛ مقدار پنل بر `.env` مقدم است.

### ✱ امنیت
- **`APP_KEY` را بعد از راه‌اندازی عوض نکن.** کلیدها و توکن‌های پنل (پیامک، ربات، هوش مصنوعی، اسنپ‌پی) با همین کلید رمز شده‌اند. اگر عوض شود، این فیلدها خالی می‌شوند و باید دوباره در پنل وارد شوند. یک نسخه از `.env` را جای امن (نه در گیت) نگه دار.
- `.env`، بکاپ‌های دیتابیس و فایل‌های `.sql` هرگز وارد گیت نمی‌شوند (`.gitignore`).
- رمز ادمین حداقل ۱۲ کاراکتر؛ برای هر نفر حساب جدا با `php artisan khaneh:admin`.
- بعد از هر `composer update`: `composer audit` باید «No security vulnerability advisories» بدهد.

## ۴. بعد از دیپلوی — آزمون دودی (۲ دقیقه)
- [ ] صفحه‌ی اصلی، یک دسته، یک محصول و جستجو باز می‌شوند.
- [ ] ورود ادمین و صفحه‌ی **ماژول‌ها**: ماژول‌های لازم روشن‌اند.
- [ ] لوگوها و فاوآیکون از `resources/brand/` در **تنظیمات فروشگاه ← عمومی** و **اپلیکیشن** گذاشته شده‌اند (راهنما: `resources/brand/README.md`).
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
