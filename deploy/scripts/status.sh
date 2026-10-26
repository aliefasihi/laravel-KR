#!/usr/bin/env bash
# Khaneh Romantic — one screen with the server's health. | نویسنده: علی فصیحی (Ali Fasihi)
#   sudo /srv/khanehromantic/bin/status.sh
APP_DIR=/srv/khanehromantic
line() { printf '\033[1m%s\033[0m\n' "$*"; }

line "Release"
echo "  $(basename "$(readlink -f "$APP_DIR/current")")  rev $(cat "$APP_DIR/current/REVISION" 2>/dev/null || echo '?')"

line "Services"
for s in nginx php8.3-fpm mariadb redis-server khaneh-queue khaneh-scheduler khaneh-backup.timer; do
    printf '  %-22s %s\n' "$s" "$(systemctl is-active "$s")"
done

line "Memory / swap"
free -h | sed 's/^/  /'
line "Disk"
df -h / | sed 's/^/  /'
du -sh "$APP_DIR/shared/storage" /srv/backups 2>/dev/null | sed 's/^/  /'
line "Traffic this month (quota 300 GB)"
if command -v vnstat >/dev/null; then vnstat -m | tail -n 4 | sed "s/^/  /"; else echo "  vnstat not installed"; fi

line "Queue"
runuser -u khaneh -- php "$APP_DIR/current/artisan" queue:failed 2>/dev/null | tail -n +1 | head -n 8 | sed 's/^/  /'

line "Certificate"
DOMAIN=$(grep -E '^APP_URL=' "$APP_DIR/shared/.env" | sed -E 's#^APP_URL=["]?https?://([^/"]+).*#\1#')
openssl x509 -enddate -noout -in "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" 2>/dev/null | sed 's/^/  /'

line "Last backup"
ls -1t /srv/backups/khanehromantic/db/ 2>/dev/null | head -n 1 | sed 's/^/  /'

line "Errors today"
LOG="$APP_DIR/shared/storage/logs/laravel-$(date +%F).log"
if [[ -f $LOG ]]; then echo "  $(grep -c '\.ERROR' "$LOG") errors in $(basename "$LOG")"; else echo "  none"; fi
