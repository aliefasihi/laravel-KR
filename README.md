# خانه رمانتیک — Store

فروشگاه ماژولار لاراولی خانه رمانتیک (جایگزین ووکامرس).
نویسنده: علی فصیحی (Ali Fasihi)

## راه‌اندازی

```bash
composer install
cp .env.example .env && php artisan key:generate
# تنظیم DB_* و WP_DB_* در .env
php artisan migrate
php artisan test
```

نیازمندی‌ها: PHP 8.2+، Composer 2، MariaDB 10.6+ (یا MySQL 8)

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
| Customer, Checkout, Order, Payment | ⚪ اسکلت | فاز ۱ |

## ماژول جدید

1. `modules/Foo/Providers/FooServiceProvider.php` که از `App\Core\Modules\ModuleServiceProvider` ارث ببرد
2. اضافه کردن به `config/modules.php`
3. بقیه (routes، migrations، views، config) با قرارداد پوشه‌ها خودکار لود می‌شود

مستندات بیشتر: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) · [docs/URL-POLICY.md](docs/URL-POLICY.md) · [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md)
