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
php artisan test
```

- فروشگاه: `/`
- پنل مدیریت: `/admin`

نیازمندی‌ها: PHP 8.2+ (با intl و gd)، Composer 2، Node 20+، MariaDB 10.6+ (یا MySQL 8)

## فرانت (فروشگاه)

- **Blade + Livewire 4 + Alpine + Tailwind 4**: صفحات کامل سمت سرور رندر می‌شوند (سئو)، تعامل‌ها با Livewire است.
- فونت‌ها لوکال‌اند (`public/fonts`، Vazirmatn و Lalezar، مجوز OFL). هیچ asset از CDN خارجی لود نمی‌شود.
- رنگ‌ها و فونت‌ها در `resources/css/app.css` (بلوک `@theme`) تعریف شده‌اند. امضای طراحی دیوار کاشی دسته‌بندی‌هاست (`.kashi-tile`).
- کامپوننت‌ها در `resources/views/components`: `site.header`، `site.footer`، `product.card`، `price`، `breadcrumbs`، `media.picture`، `category.tile`، `khatam`.
- Livewire: `catalog.search-box`، `checkout.add-to-cart`، `checkout.mini-cart`، `checkout.cart-page`.
- فیلتر دسته‌ها فرم GET معمولی است تا لینک‌ها قابل کراول باشند؛ صفحه‌ی فیلترشده `noindex` و canonical به آدرس تمیز دسته دارد.
- قیمت‌ها: `App\Core\Support\Money::toman()`؛ تاریخ: `Jalali::format()`؛ ارقام: `PersianText::toPersianDigits()`.

## پنل مدیریت (Filament 5)

- مسیر `/admin`، فارسی و راست‌به‌چپ، فقط کاربرهای `is_admin`.
- هر ماژول ریسورس‌های خودش را در `modules/{Name}/Filament/Resources` دارد و پنل خودکار پیدایشان می‌کند.
- بخش‌ها: محصولات (با مدل/قیمت/موجودی)، دسته‌بندی‌ها، برندها، مقاله‌ها، کتابخانه تصاویر، بنرها، جایگاه‌های بنر، ریدایرکت‌ها، کاربران پنل.
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
| Seo | ✅ پایه | ریدایرکت legacy یک‌مرحله‌ای، 410، URLهای `?p=` |
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
