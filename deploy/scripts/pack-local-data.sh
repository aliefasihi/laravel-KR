#!/usr/bin/env bash
# Khaneh Romantic — pack the laptop's data for the server: the database, the uploaded images and the old APP_KEY.
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   cd ~/path/to/project && bash deploy/scripts/pack-local-data.sh
#   scp /tmp/khaneh-data.tar.gz kr:/tmp/
#   ssh kr, then: sudo /srv/khanehromantic/bin/import-local-data.sh /tmp/khaneh-data.tar.gz
#
# ✱ The archive holds customers' details and the laptop's APP_KEY (so the panel secrets survive the move):
#   send it only with scp, never by chat or e-mail, and delete it after the import (the import script does on the server).
set -euo pipefail

OUT=${1:-/tmp/khaneh-data.tar.gz}
ROOT=$(git rev-parse --show-toplevel)
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
umask 077

mkdir -p "$TMP/khaneh-data"
# a consistent copy even while the local site is running
sqlite3 "$ROOT/database/database.sqlite" ".backup '$TMP/khaneh-data/database.sqlite'"
[[ $(sqlite3 "$TMP/khaneh-data/database.sqlite" 'PRAGMA integrity_check') == ok ]] || { echo "✖ the local database is damaged" >&2; exit 1; }
grep -E '^APP_KEY=' "$ROOT/.env" | cut -d= -f2- | tr -d '"' > "$TMP/khaneh-data/app-key"
git -C "$ROOT" rev-parse --short HEAD > "$TMP/khaneh-data/REVISION"

echo "▶ database: $(sqlite3 "$TMP/khaneh-data/database.sqlite" 'SELECT COUNT(*) FROM products') products, $(sqlite3 "$TMP/khaneh-data/database.sqlite" 'SELECT COUNT(*) FROM orders') orders"
# uploads with their WebP versions (the server doesn't have to rebuild them); no macOS «._» files
COPYFILE_DISABLE=1 tar --exclude='.DS_Store' -czf "$OUT" -C "$TMP" khaneh-data -C "$ROOT/storage/app" public
chmod 600 "$OUT"
echo "✔ $OUT ($(du -h "$OUT" | cut -f1)) — code $(cat "$TMP/khaneh-data/REVISION"): push it and deploy before the import"
