#!/usr/bin/env bash
# Khaneh Romantic — deploy a new release with no downtime, and roll back by itself if the site doesn't answer.
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   sudo /srv/khanehromantic/bin/deploy.sh                       # latest «main» from GitHub, built on the server
#   sudo /srv/khanehromantic/bin/deploy.sh /tmp/release.tar.gz   # a release built on the laptop (build-release.sh)
#
# Each release is a new folder in releases/; «current» is switched to it in one step (atomic), so visitors never see
# a half-copied site. storage/ and .env live in shared/ and are the same for every release. The last 3 releases stay
# for rollback.sh.
set -Eeuo pipefail

APP_DIR=/srv/khanehromantic
APP_USER=khaneh
REPO=${REPO:-git@github.com:aliefasihi/laravel-KR.git}   # private: read through the deploy key (github-key.sh)
BRANCH=${BRANCH:-main}
PHP_FPM=php8.3-fpm
KEEP=3
export PATH=/opt/node/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
export COMPOSER_HOME=$APP_DIR/.composer npm_config_cache=$APP_DIR/.npm

say()  { printf '\n\033[1;34m▶ %s\033[0m\n' "$*"; }
fail() { printf '\n\033[1;31m✖ %s\033[0m\n' "$*" >&2; exit 1; }
as_app() { runuser -u "$APP_USER" -- env HOME="$APP_DIR" PATH="$PATH" COMPOSER_HOME="$COMPOSER_HOME" npm_config_cache="$npm_config_cache" "$@"; }

[[ $EUID -eq 0 ]] || fail "با sudo اجرا کنید: sudo $0"
[[ -f $APP_DIR/shared/.env ]] || fail "فایل $APP_DIR/shared/.env وجود ندارد (بخش ۱۳ راهنما)"

exec 9>/run/khaneh-deploy.lock
flock -n 9 || fail "یک دیپلوی دیگر در حال اجراست"

SOURCE=${1:-git}
STAMP=$(date +%Y%m%d-%H%M%S)
REL=$APP_DIR/releases/$STAMP
PREV=$(readlink -f "$APP_DIR/current" 2>/dev/null || true)
SWITCHED=0

on_error() {
    if [[ $SWITCHED -eq 0 ]]; then
        rm -rf "$REL"
        fail "دیپلوی متوقف شد؛ سایت روی نسخه‌ی قبلی ماند (هیچ تغییری روی سایت زنده نیست)."
    fi
}
trap on_error ERR

say "Release $STAMP"
if [[ $SOURCE == git ]]; then
    as_app git clone --quiet --depth 1 --branch "$BRANCH" "$REPO" "$REL"
    as_app git -C "$REL" rev-parse --short HEAD > /tmp/khaneh-rev && install -o "$APP_USER" -m 644 /tmp/khaneh-rev "$REL/REVISION"
    rm -rf "$REL/.git"
else
    [[ -f $SOURCE ]] || fail "فایل $SOURCE پیدا نشد"
    install -d -o "$APP_USER" -g "$APP_USER" "$REL"
    tar -xzf "$SOURCE" -C "$REL"
    chown -R "$APP_USER:$APP_USER" "$REL"
fi

# nginx (www-data) reads the code: folders 755, files 644 — whatever the archive or umask said
chmod 755 "$APP_DIR" "$APP_DIR/releases"
chmod -R u=rwX,go=rX "$REL"

# shared storage and .env
rm -rf "$REL/storage" "$REL/.env"
as_app ln -s ../../shared/storage "$REL/storage"
as_app ln -s ../../shared/.env "$REL/.env"

cd "$REL"
if [[ $SOURCE == git ]]; then
    say "Composer (PHP packages)"
    as_app composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
    say "npm (CSS / JS build)"
    as_app npm ci --no-audit --no-fund --loglevel=error
    as_app npm run build
    rm -rf node_modules
    # packages installed from source (when zip downloads fail) carry their own .git: not needed on the server
    find vendor -name .git -type d -prune -exec rm -rf {} +
else
    [[ -d vendor && -f public/build/manifest.json ]] || fail "بسته‌ی آپلودشده vendor یا public/build ندارد (build-release.sh)"
    as_app php artisan package:discover --ansi >/dev/null
fi

as_app php artisan storage:link --quiet || true

say "Backup before migrations"
"$APP_DIR/bin/backup.sh" db-only

say "Migrations"
as_app php artisan migrate --force

say "Caches (config, routes, views, Filament)"
as_app php artisan optimize
as_app php artisan filament:optimize

say "Switching to the new release"
as_app ln -sfn "$REL" "$APP_DIR/current.new"
mv -Tf "$APP_DIR/current.new" "$APP_DIR/current"
SWITCHED=1
trap - ERR

systemctl reload "$PHP_FPM"
as_app php "$APP_DIR/current/artisan" queue:restart --quiet
systemctl restart khaneh-scheduler.service

say "Health check"
DOMAIN=$(grep -E '^APP_URL=' "$APP_DIR/shared/.env" | sed -E 's#^APP_URL=["]?https?://([^/"]+).*#\1#')
ok=0
for _ in 1 2 3 4 5; do
    if curl -fsS -o /dev/null --max-time 10 --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/up" \
       && curl -fsS -o /dev/null --max-time 20 --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/"; then
        ok=1; break
    fi
    sleep 3
done

if [[ $ok -ne 1 ]]; then
    if [[ -n $PREV && -d $PREV ]]; then
        printf '\n\033[1;31m✖ سایت روی نسخه‌ی جدید جواب نداد؛ برگشت به نسخه‌ی قبلی…\033[0m\n'
        as_app ln -sfn "$PREV" "$APP_DIR/current.new" && mv -Tf "$APP_DIR/current.new" "$APP_DIR/current"
        systemctl reload "$PHP_FPM"
        as_app php "$APP_DIR/current/artisan" queue:restart --quiet || true
        systemctl restart khaneh-scheduler.service
        rm -rf "$REL"   # the broken release must never be picked by rollback.sh
        fail "برگشت انجام شد. خطا را ببینید: tail -n 50 $APP_DIR/shared/storage/logs/laravel-$(date +%F).log"
    fi
    fail "سایت جواب نمی‌دهد و نسخه‌ی قبلی هم وجود ندارد. لاگ: $APP_DIR/shared/storage/logs/"
fi

say "Cleaning old releases (keeping $KEEP)"
ls -1dt "$APP_DIR"/releases/*/ | tail -n +$((KEEP + 1)) | xargs -r rm -rf

printf '\n\033[1;32m✔ دیپلوی %s انجام شد (%s)\033[0m\n' "$STAMP" "$(cat "$REL/REVISION" 2>/dev/null || echo upload)"
