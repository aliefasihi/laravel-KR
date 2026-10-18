# ماژول‌ها — روشن و خاموش مثل افزونه‌های وردپرس
نویسنده: علی فصیحی (Ali Fasihi)

## در پنل
**پنل ← تنظیمات ← ماژول‌ها** (`/admin/modules`)
- هر ماژول یک کارت دارد: عنوان، توضیح، نسخه، وابستگی‌ها و دکمه‌ی روشن/خاموش.
- ماژول‌های **هسته** (Media، Seo، Catalog، Search، Content، Placement، Checkout) خاموش نمی‌شوند.
- ماژولی که ماژول روشن دیگری به آن وابسته است خاموش نمی‌شود. اول وابسته‌ها را خاموش کن.
- ماژولی که وابستگی‌اش خاموش است روشن نمی‌شود.
- با روشن کردن یک ماژول:
  - migrationهای خودش اجرا می‌شود.
  - کش route، config، view و Filament پاک می‌شود.
- خاموش کردن **داده‌ای پاک نمی‌کند**. جدول‌ها می‌مانند و با روشن کردن دوباره همه‌چیز برمی‌گردد.

## پشت صحنه
| کجا | چه |
|---|---|
| `config/modules.php` | ماژول‌های **نصب‌شده** و ترتیب بوت |
| `modules/{Name}/module.json` | عنوان، توضیح، نسخه، `core`، `requires`، `default`، آیکن |
| `storage/app/modules.json` | وضعیت روشن/خاموش. بدون دیتابیس خوانده می‌شود. اگر نباشد، `default` هر ماژول ملاک است. |
| `Modules::enabled('Attributes')` | چک در کد ماژول‌های دیگر |

- `storage/app/modules.json` در گیت نیست. روی سرور بعد از دیپلوی باقی می‌ماند، پس وضعیت ماژول‌ها با هر دیپلوی ریست نمی‌شود.
- ریسورس‌ها، صفحه‌ها و ویجت‌های Filament فقط برای ماژول‌های روشن کشف می‌شوند. منوی پنل هم با خاموش شدن ماژول جمع می‌شود.

## تنظیمات فروشگاه
**پنل ← تنظیمات ← تنظیمات فروشگاه** (`/admin/settings`)
- هر ماژول روشن تب خودش را دارد: ظاهر سایت، پیامک و بعدها پرداخت و ارسال.
- تب‌ها: ظاهر سایت، پرداخت، پیامک، پیامک سفارش، نظرات.
- مقدارها در جدول `settings` ذخیره می‌شوند. در کد: `Settings::get('sms.driver')`.
- پیش‌فرض‌ها از `.env` یا config خود ماژول می‌آیند.
- ماژول تازه با `Settings::section(new SettingsSection(...))` در provider تب اضافه می‌کند.

## جای ماژول‌ها در قالب سایت
هر ماژول تکه‌ی خودش را در جاهای مشخص قالب می‌گذارد (`StorefrontHooks`):

| جا | کجا | استفاده‌ی فعلی |
|---|---|---|
| `head.end` | انتهای `<head>` | اسکریپت حالت تاریک |
| `header.actions` | کنار سبد خرید | دکمه‌ی ماه/خورشید، ورود/حساب |
| `drawer.footer` | پایین منوی موبایل | ورود، انتخاب ظاهر |
| `home.after_hero` … `home.before_journal` | صفحه‌ی اصلی | اسلایدرهای محصولات |
| `cart.summary` | خلاصه‌ی سبد و تسویه‌حساب | فیلد کد تخفیف |
| `order.payment` | صفحه‌ی سفارش مشتری | کارت به کارت (رسید)، پرداخت در محل |
| `product.tabs`، `product.sections` | صفحه‌ی محصول | نظرات |
| `body.start`، `body.end` | ابتدا و انتهای `<body>` | — |

```php
StorefrontHooks::add('header.actions', 'mymodule::partials.button', sort: 30);
```
خاموش شدن ماژول = provider بوت نمی‌شود = تکه‌اش نیست.

## ماژول تازه
1. پوشه‌ی `modules/{Name}` با `Providers/{Name}ServiceProvider.php` که از `App\Core\Modules\ModuleServiceProvider` ارث می‌برد.
2. `module.json` کنار آن.
3. اضافه کردن provider به `config/modules.php`.
4. هرجا ماژول دیگری از آن استفاده می‌کند، پشت `Modules::enabled('{Name}')` بگذار تا با خاموش شدنش چیزی نشکند.

## قیمت‌گذاری سبد (Checkout)
مبلغ سبد، تسویه‌حساب و سفارش ثبت‌شده همه از یک مسیر می‌آیند (`Quoter`):
1. کد تخفیف (Coupon) — اولویت ۱۰
2. ارسال (Shipping) — اولویت ۲۰، روی مبلغ بعد از تخفیف

ماژول تازه با `Quoter::extend('key', fn (QuoteContext $c, Quote $q) => …, priority: 30)` هزینه یا تخفیف اضافه می‌کند. روش پرداخت تازه با `PaymentMethods::register(new MyMethod)` اضافه می‌شود.

## رویدادهای سفارش
`order.placed` · `order.paid` · `order.status_changed` · `order.cancelled` · `order.shipped` · `order.refunded` · `order.customer_note`

با `Event::listen('order.paid', fn (array $order) => …)` گوش بده. داده آرایه است؛ ماژول شنونده به کلاس‌های Order وابسته نمی‌شود.
