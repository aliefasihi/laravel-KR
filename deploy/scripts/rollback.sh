#!/usr/bin/env bash
# Khaneh Romantic — go back to the previous release (or a named one) in a few seconds.
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   sudo /srv/khanehromantic/bin/rollback.sh                    # the release before the current one
#   sudo /srv/khanehromantic/bin/rollback.sh 20261101-213000    # a specific one (ls /srv/khanehromantic/releases)
#
# Code only: database migrations are not undone (they only add things, so older code keeps working with them).
set -Eeuo pipefail
APP_DIR=/srv/khanehromantic
APP_USER=khaneh
[[ $EUID -eq 0 ]] || { echo "با sudo اجرا کنید"; exit 1; }

CURRENT=$(readlink -f "$APP_DIR/current")
if [[ -n ${1:-} ]]; then
    TARGET=$APP_DIR/releases/$1
else
    # the newest release older than the current one (names are dates, so they sort in time order)
    TARGET=$(ls -1d "$APP_DIR"/releases/*/ | sed 's#/$##' | sort | awk -v cur="$CURRENT" '$0 == cur { print prev; exit } { prev = $0 }')
fi
[[ -n $TARGET && -d $TARGET ]] || { echo "نسخه‌ای برای برگشت پیدا نشد"; ls -1 "$APP_DIR/releases"; exit 1; }

echo "current : $(basename "$CURRENT")"
echo "rollback: $(basename "$TARGET")"
runuser -u "$APP_USER" -- ln -sfn "$TARGET" "$APP_DIR/current.new"
mv -Tf "$APP_DIR/current.new" "$APP_DIR/current"
systemctl reload php8.3-fpm
runuser -u "$APP_USER" -- php "$APP_DIR/current/artisan" queue:restart --quiet
systemctl restart khaneh-scheduler.service
echo "✔ سایت روی $(basename "$TARGET") است"
