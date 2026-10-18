# Sms — پیامک
نویسنده: علی فصیحی (Ali Fasihi)

**پنل ← تنظیمات ← تنظیمات فروشگاه ← پیامک** (یا `.env`: `SMS_DRIVER` و بقیه)

| سرویس | کد ورود | تنظیم در پنل سرویس |
|---|---|---|
| `log` | فقط در `storage/logs/laravel.log` | — (برای لوکال) |
| کاوه‌نگار | الگوی verify/lookup | یک الگو با `%token%`، مثلاً «کد ورود شما به خانه رمانتیک: %token%» |
| sms.ir | ارسال سریع (verify) | قالب با `#CODE#`؛ شناسه‌ی قالب در تنظیمات |

برای پر شدن خودکار کد در اندروید، خط آخر متن الگو باید این باشد:
```
@khanehromantic.com #%token%
```
(در sms.ir: `#CODE#`)

## در کد
```php
app(\Modules\Sms\Support\Sms::class)->otp('09121234567', '48213');
app(\Modules\Sms\Support\Sms::class)->template('09121234567', 'order-sent', ['KR-1042', 'پست']);
app(\Modules\Sms\Support\Sms::class)->text('09121234567', 'سلام!');
```
خطای سرویس لاگ می‌شود و `SmsException` می‌دهد.
