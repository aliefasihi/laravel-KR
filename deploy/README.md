# deploy/ — راه‌اندازی و نگهداری سرور خانه رمانتیک
نویسنده: علی فصیحی (Ali Fasihi)

راهنمای کامل و قدم‌به‌قدم: **«راهنمای راه‌اندازی سرور خانه رمانتیک» (PDF)**. این پوشه فایل‌هایی است که آن راهنما روی سرور کپی می‌کند.

| پوشه | چیست | مقصد روی سرور |
|---|---|---|
| `nginx/` | سایت، تنظیمات عمومی، رد درخواست‌های ناشناس، TLS، هدرها، آدرس آزمایشی | `/etc/nginx/…` |
| `php/` | pool اختصاصی php-fpm (کاربر khaneh) و تنظیمات OPcache | `/etc/php/8.3/…` |
| `mariadb/` | تنظیم حافظه و کاراکترست | `/etc/mysql/mariadb.conf.d/` |
| `redis/` | رمز، سقف حافظه، ماندگاری صف | `/etc/redis/khaneh.conf` |
| `systemd/` | صف، زمان‌بند، بکاپ شبانه | `/etc/systemd/system/` |
| `system/` | کرنل، لاگ سیستم، SSH، fail2ban، آپدیت امنیتی | `/etc/…` |
| `scripts/` | `deploy.sh`، `rollback.sh`، `backup.sh`، `status.sh`، `reload-env.sh`، `check-network.sh`، `build-release.sh` | `/srv/khanehromantic/bin/` |
| `env/` | الگوی `.env` سرور | `/srv/khanehromantic/shared/.env` |
| `docker/`، `services/` | برای سرویس‌های بعدی (n8n و …) | `/etc/docker/`، `/srv/services/` |

## ساختار سرور
```
/srv/khanehromantic/
├── current -> releases/20261101-213000   ← nginx و php-fpm از این‌جا اجرا می‌کنند
├── releases/                              ← ۳ نسخه‌ی آخر (برای برگشت)
├── shared/.env                            ← تنظیمات و رمزها (۶۰۰)
├── shared/storage/                        ← تصاویر، لاگ‌ها، کش
└── bin/                                   ← اسکریپت‌ها (مالک root)
/srv/backups/khanehromantic/               ← بکاپ‌ها
/srv/services/<name>/                      ← سرویس‌های Docker بعدی (n8n، …)
```

## کارهای روزمره
```bash
sudo /srv/khanehromantic/bin/deploy.sh        # نسخه‌ی جدید از گیت‌هاب
sudo /srv/khanehromantic/bin/rollback.sh      # برگشت به نسخه‌ی قبلی
sudo /srv/khanehromantic/bin/status.sh        # وضعیت سرور
sudo /srv/khanehromantic/bin/reload-env.sh    # بعد از ویرایش .env
```
