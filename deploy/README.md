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

## سرور بدون اینترنت بین‌الملل
`check-network.sh` نشان می‌دهد سرور به کدام سرویس‌ها می‌رسد. اگر GitHub، npm، Packagist یا Let's Encrypt بسته بود:

### دیپلوی از روی لپ‌تاپ
```bash
# روی مک (VPN روشن برای npm و Packagist)، داخل پوشه‌ی پروژه — از آخرین commit ساخته می‌شود
bash deploy/scripts/build-release.sh
# VPN خاموش
scp /tmp/khaneh-release.tar.gz kr:/tmp/
# روی سرور
sudo /srv/khanehromantic/bin/deploy.sh /tmp/khaneh-release.tar.gz
```
Node.js و کلید گیت‌هاب روی سرور لازم نیست.

### گواهی HTTPS از روی لپ‌تاپ (چالش DNS)
DNS دامنه روی آروان است. روی مک، با VPN روشن:
```bash
curl https://get.acme.sh | sh -s email=<ایمیل>          # فقط بار اول
D=khanehromantic.ir; P=www
~/.acme.sh/acme.sh --issue --force --server letsencrypt --dns -d $D -d $P.$D --keylength ec-256 \
  --yes-I-know-dns-manual-mode-enough-go-ahead-please
```
دو مقدار `TXT value` چاپ می‌شود. در آروان، بخش «رکوردهای DNS»، دو رکورد TXT بسازید (یا مقدار قبلی را عوض کنید):

| نام | مقدار |
|---|---|
| `_acme-challenge` | مقدارِ `_acme-challenge.khanehromantic.ir` |
| `_acme-challenge.www` | مقدارِ `_acme-challenge.www.khanehromantic.ir` |

وقتی هر دو دیده شدند، گواهی را بگیرید و به سرور بفرستید:
```bash
dig +short TXT _acme-challenge.$D @n.ns.arvancdn.ir; dig +short TXT _acme-challenge.$P.$D @n.ns.arvancdn.ir
~/.acme.sh/acme.sh --renew --server letsencrypt -d $D --ecc --yes-I-know-dns-manual-mode-enough-go-ahead-please
# VPN خاموش
bash deploy/scripts/push-cert.sh $D
```
**تمدید:** گواهی ۹۰ روزه است. حدود ۶۰ روز بعد همین سه مرحله را تکرار کنید (روش دستی خودکار تمدید نمی‌شود).
اگر کلید API آروانِ کاربر ماشینی به DNS دسترسی داشته باشد (`curl -H "Authorization: Apikey …" https://napi.arvancloud.ir/cdn/4.0/domains/$D` → 200)،
به‌جای `--dns` بنویسید `--dns dns_arvan` تا رکوردها خودکار ساخته و پاک شوند.

### داده‌ی لپ‌تاپ و طول ستون‌ها
SQLite لپ‌تاپ طول ستون‌ها را کنترل نمی‌کند ولی MariaDB می‌کند. اگر `import-local-data.sh` با
`Data too long for column` متوقف شد: سایت در حالت تعمیر می‌ماند و بکاپ گرفته شده؛ ستون را با یک migration بزرگ کنید
(مثل `2026_10_29_000100_widen_banners_external_id`)، دیپلوی کنید و ورود را دوباره بزنید — هر جدول از اول پر می‌شود.
