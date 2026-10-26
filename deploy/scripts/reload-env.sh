#!/usr/bin/env bash
# Khaneh Romantic — after editing /srv/khanehromantic/shared/.env: rebuild the config cache and restart PHP and workers.
# نویسنده: علی فصیحی (Ali Fasihi)
#   sudo nano /srv/khanehromantic/shared/.env && sudo /srv/khanehromantic/bin/reload-env.sh
set -Eeuo pipefail
[[ $EUID -eq 0 ]] || { echo "با sudo اجرا کنید"; exit 1; }
cd /srv/khanehromantic/current
runuser -u khaneh -- php artisan optimize
runuser -u khaneh -- php artisan filament:optimize
systemctl reload php8.3-fpm
runuser -u khaneh -- php artisan queue:restart --quiet
systemctl restart khaneh-scheduler.service
echo "✔ تنظیمات تازه اعمال شد"
