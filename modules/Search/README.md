# Search
جستجوی محصولات، فیلترهای صفحات لیست و گزارش جستجو.
نویسنده: علی فصیحی (Ali Fasihi)

## موتور
با `SEARCH_DRIVER` در `.env` انتخاب می‌شود. همه‌ی صفحه‌ها از `ProductFinder` استفاده می‌کنند، پس عوض کردن موتور به کد دست نمی‌زند.

| مقدار | کِی | غلط تایپی | رم |
|---|---|---|---|
| `database` (پیش‌فرض) | لوکال، کاتالوگ چند هزار تایی | ندارد | — |
| `meilisearch` | **پیشنهادی برای production** | دارد | ~۱۰۰–۳۰۰ مگ |
| `elasticsearch` | کاتالوگ خیلی بزرگ یا تحلیل لاگ | دارد | ۲ گیگ به بالا |

- اگر Meilisearch یا Elasticsearch در دسترس نباشد، صفحه‌ها بی‌صدا با `database` کار می‌کنند و موتور ۳۰ ثانیه کنار گذاشته می‌شود.
- جدول `product_search_texts` همیشه به‌روز می‌ماند، چون همان fallback است.

## چه چیزهایی یکی دیده می‌شوند (`Support/SearchText`)
- ي/ك با ی/ک
- نیم‌فاصله با فاصله
- ارقام فارسی با ارقام انگلیسی
- اِعراب و کشیده حذف می‌شوند
- آ مثل ا
- SKU با خط تیره و بدون آن: `GK-7710` = `gk7710`
- مترادف‌ها در `modules/Search/config.php` تعریف می‌شوند. بعد از تغییرشان: `php artisan search:rebuild`

## رتبه‌بندی
- اول تطابق در نام، بعد برند و دسته، بعد توضیحات.
- کالای موجود همیشه قبل از ناموجود، حتی در مرتب‌سازی قیمت.
- تعداد کنار هر برند در فیلتر، فیلتر برند خودش را نادیده می‌گیرد؛ پس با انتخاب یک برند، تعداد برندهای دیگر صفر نمی‌شود. بازه‌ی قیمت هم همین‌طور است.

## همگام‌سازی
- ذخیره‌ی محصول، مدل، برند یا دسته شناسه‌ها را جمع می‌کند. در پایان درخواست، **یک job صف** ایندکس را به‌روز می‌کند.
  - نیاز است: `php artisan queue:work`
- `search:sync-prices` هر دقیقه از scheduler اجرا می‌شود و محصولاتی را که تخفیفشان شروع یا تمام شده دوباره ایندکس می‌کند.
  - نیاز است: کرون `* * * * * php artisan schedule:run`

## دستورها
```bash
php artisan search:rebuild     # ساخت دوباره‌ی ایندکس (بعد از دیپلوی اول، ایمپورت وردپرس، تغییر مترادف‌ها)
php artisan search:status      # موتور، در دسترس بودن، تعداد سندها
php artisan search:sync-prices # دستی (معمولاً scheduler اجرایش می‌کند)
```

## مسیرها
- `GET /search?q=`: صفحه‌ی نتایج (در Catalog)، با همان فیلترهای صفحه‌ی دسته‌ها
- `GET /search/suggest?q=`: JSON پیشنهادهای لحظه‌ای (محصول، دسته، برند). بدون session و cookie؛ throttle ۱۲۰ در دقیقه.

## پنل
**بازاریابی ← گزارش جستجو:** هر عبارت، تعداد جستجو و تعداد دفعاتی که نتیجه نداشته.
- ردیف‌های «بی‌نتیجه» یعنی محصولی که مشتری می‌خواهد و نداریم، یا کلمه‌ای که باید به مترادف‌ها اضافه شود.

## راه‌اندازی Meilisearch (Docker، کنار Caddy)
```yaml
meilisearch:
  image: getmeili/meilisearch:v1.15
  restart: unless-stopped
  environment:
    MEILI_MASTER_KEY: "یک-کلید-طولانی-تصادفی"
    MEILI_ENV: production
    MEILI_NO_ANALYTICS: "true"
  volumes: ["./meili_data:/meili_data"]
```
```dotenv
SEARCH_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=همان-MEILI_MASTER_KEY
```
بعد: `php artisan search:rebuild`

تست واقعی با Meilisearch:
```bash
MEILISEARCH_TEST_HOST=http://127.0.0.1:7700 MEILISEARCH_TEST_KEY=… php artisan test --filter=Meilisearch
```
