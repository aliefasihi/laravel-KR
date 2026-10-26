#!/usr/bin/env bash
# Khaneh Romantic — put the laptop's data on the server (instead of the WordPress import; again on the switch night).
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   sudo /srv/khanehromantic/bin/import-local-data.sh /tmp/khaneh-data.tar.gz     (made by pack-local-data.sh)
#
# The server's data is replaced by the laptop's (a database backup is taken first), the images are added to
# shared/storage, the panel secrets are re-encrypted with this server's APP_KEY, then search and caches are rebuilt.
# The archive is deleted at the end: it holds customers' details.
set -Eeuo pipefail

APP_DIR=/srv/khanehromantic
APP_USER=khaneh
ARCHIVE=${1:-/tmp/khaneh-data.tar.gz}

say()  { printf '\n\033[1;34m▶ %s\033[0m\n' "$*"; }
fail() { printf '\n\033[1;31m✖ %s\033[0m\n' "$*" >&2; exit 1; }
as_app() { runuser -u "$APP_USER" -- "$@"; }

[[ $EUID -eq 0 ]] || fail "با sudo اجرا کنید: sudo $0 $ARCHIVE"
[[ -f $ARCHIVE ]] || fail "فایل $ARCHIVE پیدا نشد (روی مک: bash deploy/scripts/pack-local-data.sh)"
[[ -L $APP_DIR/current ]] || fail "اول یک بار دیپلوی کنید: sudo $APP_DIR/bin/deploy.sh"

WORK=$(mktemp -d /tmp/khaneh-import.XXXXXX)
trap 'rm -rf "$WORK"' EXIT
tar -xzf "$ARCHIVE" -C "$WORK"
chown -R "$APP_USER:$APP_USER" "$WORK"
[[ -f $WORK/khaneh-data/database.sqlite ]] || fail "بسته‌ی داده ناقص است"
echo "data of code $(cat "$WORK/khaneh-data/REVISION" 2>/dev/null || echo '?'), server runs $(cat "$APP_DIR/current/REVISION" 2>/dev/null || echo '?')"

say "Backup of the server's current database"
"$APP_DIR/bin/backup.sh" db-only

cd "$APP_DIR/current"
as_app php artisan down --retry=30 || true
trap 'rm -rf "$WORK"; printf "\n\033[1;31m✖ ورود داده متوقف شد؛ سایت در حالت تعمیر ماند. بکاپ قبل از ورود در /srv/backups/khanehromantic/db است.\033[0m\n" >&2' ERR

say "Database"
as_app php artisan khaneh:copy-database "$WORK/khaneh-data/database.sqlite" --old-key-file="$WORK/khaneh-data/app-key" --force

say "Images"
rsync -a "$WORK/public/" "$APP_DIR/shared/storage/app/public/"
chown -R "$APP_USER:$APP_USER" "$APP_DIR/shared/storage/app/public"

say "Search, image versions and caches"
as_app php artisan cache:clear --quiet
as_app php artisan search:rebuild
as_app php artisan media:optimize --queue
as_app php artisan up

rm -f "$ARCHIVE"
printf '\n\033[1;32m✔ داده‌های لپ‌تاپ روی سرور است (فایل %s پاک شد).\033[0m\n' "$ARCHIVE"
