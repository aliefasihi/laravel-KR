# معماری

نویسنده: علی فصیحی (Ali Fasihi)

## سه سیستم، یک قرارداد

```
[Store — این ریپو]  ←— Store API v1 + Webhooks (Outbox) —→  [سوپراپ خانه رمانتیک]
      منبع حقیقت                                              ↓        ↓        ↓
                                                         ai-platform   n8n   API گرافیک
```

- **Store** منبع حقیقت محصول، سفارش، محتوای منتشرشده و بنرهاست.
- **سوپراپ** ارکستریتور است: تولید، پیش‌نمایش و تأیید، سپس push از طریق API.
- **ai-platform** موتور تولید محتواست. سوپراپ آن را صدا می‌زند و منطقش کپی نمی‌شود.
- **n8n** فقط برای کارهای جانبی است (نوتیف، گزارش، تریگر محتوا). پرداخت، موجودی و وضعیت سفارش فقط در Store مدیریت می‌شود.

## ماژولار مونولیت

- یک اپ لاراول با ماژول‌های مستقل در `modules/`. میکروسرویس نیست.
- هر ماژول با `ModuleServiceProvider` routes، migrations، views و config خودش را با قرارداد پوشه لود می‌کند.
- **وابستگی بین ماژول‌ها از طریق رویداد است**، نه فراخوانی مستقیم. مثال: Catalog رویداد `ProductUrlChanged` را منتشر می‌کند و Seo گوش می‌دهد.
- وابستگی‌های مجاز فعلی:
  - همه به `App\Core`
  - Catalog، Content و Placement به Media
  - Seo به رویدادهای Catalog
  - FileManager به Media و Catalog
  - Attributes به Catalog و Search (فیلدهای سند جستجو را با `ProductDocuments::extend()` اضافه می‌کند)
  - HomeSliders به Catalog؛ Customer به Sms
  - Coupon و Shipping به Checkout (پله‌های `Quoter`)؛ Order به Customer و Checkout؛ Payment به Order؛ Reviews به Catalog و Customer
  - Coupon به رویدادهای رشته‌ای Order گوش می‌دهد (`order.placed`، `order.cancelled`)، نه به کلاس‌هایش
  - Appearance، HomeSliders و Customer تکه‌هایشان را با `StorefrontHooks` در قالب می‌گذارند و تنظیماتشان را با `Settings::section()`
- ماژول‌های غیرهسته از پنل روشن و خاموش می‌شوند (`docs/MODULES.md`). هرجا ماژول دیگری از آن‌ها استفاده می‌کند، پشت `Modules::enabled()` است.

## قراردادهای داده

- پول: integer ریال. تبدیل به تومان فقط در لایه‌ی نمایش.
- زمان: ذخیره در UTC، نمایش با `khaneh.display_timezone` (Asia/Tehran).
- متن فارسی: برای جستجو و مقایسه با `PersianText::normalize()`.
- `legacy_*`: شناسه‌های وردپرس برای ایمپورت idempotent.
- `external_id`: شناسه در سیستم‌های بیرونی (ai-platform، سوپراپ).
- محتوای جدید به‌صورت `content_blocks` (JSON). HTML قدیمی فقط تا زمان تبدیل نگه داشته می‌شود.

## قدم‌های بعدی

1. ایمپورتر WordPress: برند و دسته، تصاویر، محصول، مقاله، بازنویسی لینک‌ها، ریدایرکت‌ها
2. ایمپورت مشتریان و سفارش‌های ووکامرس (WordPressImport)
3. `php artisan install:api` (Sanctum) و abilityهای Store API
4. dispatcher صف برای Outbox با وب‌هوک‌های امضاشده‌ی HMAC
5. کش کامل صفحه برای کاربر مهمان + نسخه‌های avif/webp تصاویر
