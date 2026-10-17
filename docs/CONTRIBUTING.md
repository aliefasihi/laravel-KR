# قراردادهای گیت و کد

نویسنده: علی فصیحی (Ali Fasihi)

## برنچ‌ها

- `main`: همیشه قابل دیپلوی؛ مستقیم روی آن کامیت نشود.
- `feature/<module>-<topic>`، مثلاً `feature/catalog-variants` یا `feature/import-media`
- `fix/<topic>` و `chore/<topic>`
- ادغام با Pull Request یا `git merge --no-ff` بعد از سبز شدن CI

## پیام کامیت (Conventional Commits)

```
<type>(<module>): <خلاصه، انگلیسی، زمان حال>
```

| type | کاربرد |
|---|---|
| `feat` | قابلیت جدید |
| `fix` | رفع باگ |
| `refactor` | تغییر ساختار بدون تغییر رفتار |
| `test` | تست |
| `docs` | مستندات |
| `chore` | تنظیمات، وابستگی‌ها، ابزار |
| `perf` | بهبود سرعت |

scope معمولاً نام ماژول است: `catalog`، `seo`، `media`، `content`، `placement`، `integration`، `import`، `core`.

مثال: `feat(import): map WooCommerce variations to product_variants`

## قبل از هر کامیت

```bash
vendor/bin/pint        # استایل کد
php artisan test       # همه‌ی تست‌ها، از جمله modules/*/Tests
```

## قواعد کد

- هر فایل PHP جدید هدر نویسنده دارد: `نویسنده: علی فصیحی (Ali Fasihi)`
- منطق یک ماژول داخل همان ماژول بماند؛ ارتباط بین ماژول‌ها با رویداد باشد
- migration جدید فقط داخل `modules/{Name}/database/migrations`
- هیچ داده‌ی واقعی (dump دیتابیس، اکسپورت کراول، `.env`) وارد گیت نشود؛ مسیرها در `.gitignore` هستند
- هر تغییر در سیاست URL ← به‌روزرسانی `docs/URL-POLICY.md` و نوشتن یک ADR
