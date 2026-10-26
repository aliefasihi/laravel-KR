#!/usr/bin/env bash
# Khaneh Romantic — backup: database (every night and before every deploy), uploads and .env (every night).
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   sudo /srv/khanehromantic/bin/backup.sh            # full (what the nightly timer runs)
#   sudo /srv/khanehromantic/bin/backup.sh db-only    # database only (deploy.sh)
#
# Local copies in /srv/backups/khanehromantic (the disk is 30 GB: 7 databases, 3 file sets).
# If rclone has a remote called «offsite» (guide, section 16), everything is also copied off the server —
# a backup that lives only on the same server is lost with it.
set -Eeuo pipefail
APP_DIR=/srv/khanehromantic
DEST=/srv/backups/khanehromantic
DB=$(grep -E '^DB_DATABASE=' "$APP_DIR/shared/.env" | cut -d= -f2- | tr -d '"')
NAME=$(basename "$DB")
STAMP=$(date +%Y%m%d-%H%M%S)
umask 077
mkdir -p "$DEST/db" "$DEST/files"

mariadb-dump --single-transaction --quick --routines --triggers --events \
    --default-character-set=utf8mb4 "$DB" | gzip -6 > "$DEST/db/$NAME-$STAMP.sql.gz.part"
mv "$DEST/db/$NAME-$STAMP.sql.gz.part" "$DEST/db/$NAME-$STAMP.sql.gz"
ls -1t "$DEST"/db/*.sql.gz | tail -n +8 | xargs -r rm -f
echo "db: $DEST/db/$NAME-$STAMP.sql.gz ($(du -h "$DEST/db/$NAME-$STAMP.sql.gz" | cut -f1))"

if [[ ${1:-} == db-only ]]; then chgrp -R sudo "$DEST" && chmod -R g+rX "$DEST"; exit 0; fi

# uploads and private keys; the WebP/AVIF versions are left out (`php artisan media:optimize` rebuilds them)
tar -czf "$DEST/files/storage-$STAMP.tar.gz.part" -C "$APP_DIR/shared" \
    --exclude='storage/app/public/media/conversions' --exclude='storage/framework' --exclude='storage/logs' \
    storage .env
mv "$DEST/files/storage-$STAMP.tar.gz.part" "$DEST/files/storage-$STAMP.tar.gz"
ls -1t "$DEST"/files/*.tar.gz | tail -n +4 | xargs -r rm -f
echo "files: $DEST/files/storage-$STAMP.tar.gz ($(du -h "$DEST/files/storage-$STAMP.tar.gz" | cut -f1))"

# admins (the «sudo» group) can read the backups, e.g. to copy them to the laptop with rsync; nobody else can
chgrp -R sudo "$DEST" && chmod -R g+rX "$DEST" && chmod 750 "$DEST"

if command -v rclone >/dev/null && rclone listremotes 2>/dev/null | grep -qx 'offsite:'; then
    rclone copy "$DEST" "offsite:khanehromantic-backups/$(hostname)" --max-age 48h --transfers 2 --quiet \
        && echo "offsite: copied" || echo "offsite: FAILED (the local copies are fine)" >&2
fi
