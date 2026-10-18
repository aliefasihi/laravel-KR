# خانه رمانتیک — Store

فروشگاه ماژولار لاراولی خانه رمانتیک (جایگزین ووکامرس).
نویسنده: علی فصیحی (Ali Fasihi)

## راه‌اندازی

```bash
composer install                 # Laravel 12، Livewire 4، Filament 5
npm install && npm run build     # Tailwind 4 + Vite
cp .env.example .env && php artisan key:generate
# تنظیم DB_* و WP_DB_* در .env
php artisan migrate --seed       # جایگاه‌های بنر (+ داده‌ی نمونه در محیط local)
php artisan storage:link
php artisan khaneh:admin you@example.com --name="Ali Fasihi"
php artisan search:rebuild       # ایندکس جستجو
php artisan media:optimize       # نسخه‌های WebP تصاویر
php artisan queue:work           # (پروسه‌ی جدا) ایندکس جستجو و تصاویر از صف ساخته می‌شوند
php artisan test
```

- فروشگاه: `/`
- پنل مدیریت: `/admin`

نیازمندی‌ها: PHP 8.2+ (با intl و gd)، Composer 2، Node 20+، MariaDB 10.6+ (یا MySQL 8)

## فرانت (فروشگاه)

- **Blade + Livewire 4 + Alpine + Tailwind 4**: صفحات کامل سمت سرور رندر می‌شوند (سئو)، تعامل‌ها با Livewire است.
- **سبک طراحی: Glassmorphism** با الهام از سایت فعلی؛ پالت برند: سرمه‌ای `#23284f`، رز `#cc3366`، کاراملی `#e4a96c`، کرم `#fff5ed`.
  توکن‌ها در `resources/css/app.css` (بلوک `@theme`)، ابزارها با `@utility`: `glass`، `glass-strong`، `glass-navy`، `btn-navy`، `btn-rose`، `btn-glass`، `btn-icon`.
  کلاس‌های کمکی: `.section-title` (تیتر دو رنگ + کلمه‌ی لاتین محو پشتش)، `.rail` (اسلایدر افقی snap)، `.chip`، `.badge-off`، `.prose-fa`.
- فونت: Vazirmatn لوکال (OFL). فونت سایت فعلی (کلمه) لایسنسی است؛ اگر فایلش را داری در `public/fonts/kalameh/` بگذار و `KHANEH_FONT=kalameh` کن (راهنما: `public/fonts/kalameh/README.md`).
- لوگو: `public/images/logo.webp` (اگر نباشد، مونوگرام نیلوفر + نام فروشگاه نمایش داده می‌شود).
- اطلاعات تماس، شبکه‌های اجتماعی، نوار تخفیف بالای سایت: `config/khaneh.php` (کلیدهای `store` و `promo`، قابل تنظیم با `KHANEH_*` در `.env`).
- کامپوننت‌ها در `resources/views/components`:
  `site.header` (مگامنو ۳ سطحی + منوی کشویی موبایل)، `site.footer`، `site.promo`، `site.bottom-nav` (نوار پایین موبایل)، `site.whatsapp`،
  `section-title`، `product.card`، `listing` (فیلتر + گرید مشترک دسته/برند/فروشگاه)، `price`، `breadcrumbs`، `media.picture`، `post.card`، `ui.toast`.
- Livewire فقط جایی که state سمت سرور لازم است: `checkout.add-to-cart`، `checkout.mini-cart` (افزودن سریع از کارت‌ها را هم می‌گیرد)، `checkout.cart-page`.
  جستجوی هدر (`<x-search-box>`) با Alpine و endpoint سبک `/search/suggest` کار می‌کند.
- صفحه‌ی اصلی: اسلایدر (`home.hero`)، پیشنهاد ویژه با شمارش معکوس، دایره‌های دسته‌بندی، «محصولات شگفت‌انگیز»، جدیدترین‌ها، بنرهای `home.duo` و `home.wide`، برندها، مقاله‌ها.
  تصویر دایره‌ی دسته‌ها و لوگوی برندها از پنل (فیلد تصویر/لوگو) انتخاب می‌شود.
- مسیرها: `/shop` (همه‌ی محصولات)، `/brand/{slug}`، `/search`، `/search/suggest`، `/blog`، `/aboutus`، `/contact-us`، `POST /newsletter` (عضویت خبرنامه با ایمیل یا موبایل).
- **جستجو** (ماژول Search): موتور قابل انتخاب (`database` / `meilisearch` / `elasticsearch`)، غلط‌یابی، مترادف، پیشنهاد لحظه‌ای، تعداد کنار فیلترها، گزارش جستجو. ← `modules/Search/README.md`
- **سرعت**: کش صفحه با باطل‌سازی خودکار، WebP واکنش‌گرا، prerender لینک‌ها، شیشه‌ی سبک. چک‌لیست سرور ← `docs/PERFORMANCE.md`
- فیلترها فرم GET معمولی است تا لینک‌ها قابل کراول باشند؛ صفحه‌ی فیلترشده `noindex` و canonical به آدرس تمیز دسته دارد.
- قیمت‌ها: `App\Core\Support\Money::toman()`؛ تاریخ: `Jalali::format()`؛ ارقام: `PersianText::toPersianDigits()`.

## پنل مدیریت (Filament 5)

- مسیر `/admin`، فارسی و راست‌به‌چپ، فقط کاربرهای `is_admin`.
- هر ماژول ریسورس‌های خودش را در `modules/{Name}/Filament/Resources` دارد و پنل خودکار پیدایشان می‌کند.
- بخش‌ها: محصولات (با مدل/قیمت/موجودی)، دسته‌بندی‌ها، برندها، مقاله‌ها، کتابخانه تصاویر، بنرها، جایگاه‌های بنر، گزارش جستجو، ریدایرکت‌ها، پیشنهادهای ریدایرکت، کاربران پنل.
- قیمت در پنل به **تومان** وارد می‌شود و در دیتابیس **ریال** ذخیره می‌شود.
- داشبورد: محصولات ناموجود، بدون تصویر، پیش‌نویس‌ها و ورودی از آدرس‌های قدیمی.

## ساختار

```
app/Core/                 هسته‌ی مشترک (بدون منطق فروشگاه)
  Modules/                ModuleServiceProvider پایه + CoreServiceProvider
  Support/                PersianText، UrlPath
  Http/Middleware/        CanonicalTrailingSlash
config/
  modules.php             لیست ماژول‌های فعال (ترتیب = ترتیب بوت)
  khaneh.php              تنظیمات فروشگاه و سیاست URL
modules/{Name}/           هر ماژول مستقل:
  Providers/              {Name}ServiceProvider (اجباری)
  Models/ Http/ Support/ Events/ Listeners/ View/
  routes/web.php          ← خودکار با middleware web
  routes/api.php          ← خودکار زیر /api/v1
  database/migrations/    ← خودکار
  resources/views/        ← namespace: {name}::
  config.php              ← config('modules.{name}')
  Tests/                  تست‌های ماژول
  README.md
docs/                     معماری، سیاست URL، قراردادهای گیت، ADRها
```

| ماژول | وضعیت | مسئولیت |
|---|---|---|
| Catalog | ✅ پایه | برند، دسته (URL حفظ‌شده)، محصول `/p/{brand}-{sku}`، ورییشن |
| Seo | ✅ پایه | ریدایرکت legacy یک‌مرحله‌ای، 410، URLهای `?p=`، پیشنهاد ریدایرکت برای ۴۰۴های قدیمی |
| Search | ✅ | موتور جستجو (database/Meilisearch/Elasticsearch)، فیلتر و facet، پیشنهاد لحظه‌ای، گزارش جستجو |
| Media | ✅ پایه | کتابخانه‌ی تصاویر، `HasMedia` |
| Content | ✅ پایه | مقاله‌ها `/blog/{slug}`، مقصد سینک ai-platform |
| Placement | ✅ پایه | جایگاه‌های بنر برای سوپراپ، `<x-placement>` |
| Integration | ✅ پایه | Store API v1، Outbox |
| WordPressImport | 🟡 تنظیمات | اتصال read-only به دیتابیس ووکامرس |
| Checkout | 🟡 سبد خرید | سبد session، افزودن به سبد، مینی‌کارت؛ ثبت سفارش در فاز بعد |
| Customer, Order, Payment | ⚪ اسکلت | فاز ۱ |

## ماژول جدید

1. `modules/Foo/Providers/FooServiceProvider.php` که از `App\Core\Modules\ModuleServiceProvider` ارث ببرد
2. اضافه کردن به `config/modules.php`
3. بقیه (routes، migrations، views، config) با قرارداد پوشه‌ها خودکار لود می‌شود

مستندات بیشتر: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) · [docs/URL-POLICY.md](docs/URL-POLICY.md) · [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md)
