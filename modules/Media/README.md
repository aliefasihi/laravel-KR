# Media
کتابخانه‌ی تصاویر. هر مدل با trait `HasMedia` گالری/کاور می‌گیرد.
`legacy_url` آدرس اصلی در `wp-content/uploads` را نگه می‌دارد برای بازنویسی محتوا و ریدایرکت تصاویر.

## نسخه‌های واکنش‌گرا (سرعت)
- هر تصویری که آپلود یا ایمپورت می‌شود، با یک job صف نسخه‌های WebP در عرض‌های ۳۲۰، ۶۴۰، ۹۶۰ و ۱۴۴۰ پیکسل می‌گیرد.
  - اگر GD از AVIF پشتیبانی کند، نسخه‌ی AVIF هم ساخته می‌شود.
  - مسیر: `storage/app/public/media/conversions/{id}/`
- `<x-media.picture>` و `<x-media.banner>` با `srcset` و `sizes` کاری می‌کنند که مرورگر فقط اندازه‌ی لازم را دانلود کند.
- در پنل، تصویرهای کوچک (`Media::urlFor(160)`) نشان داده می‌شوند، نه فایل اصلی.

```bash
php artisan media:optimize          # فقط تصاویری که نسخه ندارند (بعد از ایمپورت وردپرس)
php artisan media:optimize --force  # ساخت دوباره‌ی همه
php artisan media:optimize --queue  # سپردن به صف
```

