# Checkout
نویسنده: علی فصیحی (Ali Fasihi)

سبد خرید session (شناسه‌ی ورییشن → تعداد).
- قیمت همیشه زنده از دیتابیس خوانده می‌شود.
- تعداد به موجودی محدود است.

- `Support/Cart`: add / set / remove / lines / subtotal / coupon / quote
- `Support/Pricing`
  - `QuoteContext`: سبد و انتخاب‌های مشتری
  - `Quote`: جمع، تخفیف، ارسال، مبلغ کل
  - `Quoter`: پله‌هایی که Coupon و Shipping اضافه می‌کنند
- Livewire: `checkout.add-to-cart`، `checkout.mini-cart`، `checkout.cart-page`
- مسیر: `/cart` (noindex)
- ثبت سفارش در ماژول Order، پرداخت در ماژول Payment.
