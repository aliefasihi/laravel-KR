# Integration
Store API v1 (`/api/v1`)، وب‌هوک‌ها و Outbox.

- رویدادها با `OutboxEvent::record()` داخل همان تراکنش ثبت می‌شوند
- قدم بعد: `php artisan install:api` (Sanctum) + abilityهای جدا برای سوپراپ، ai-platform و n8n
