# Catalog
برند، دسته‌بندی، محصول و ورییشن.

- دسته‌ها: URL دقیقاً مثل وردپرس — `/product-category/{path}/` با اسلش آخر (`Category::url()`)
- محصول: `/p/{brand}-{sku}` — اسلاگ همیشه از برند + SKU ساخته می‌شود و دستی تنظیم نمی‌شود (`Support/ProductSlug`)
- تغییر SKU یا اسلاگ برند ← رویداد `ProductUrlChanged` ← ماژول Seo ریدایرکت ۳۰۱ می‌سازد
- قیمت‌ها integer ریالی، روی ورییشن
