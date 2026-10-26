#!/usr/bin/env bash
# Khaneh Romantic — build a release on the laptop (when the server can't reach GitHub, Packagist or npm).
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   cd ~/path/to/project && bash deploy/scripts/build-release.sh
#   scp -P <SSH port> /tmp/khaneh-release.tar.gz ali@<server IP>:/tmp/
#   ssh … then: sudo /srv/khanehromantic/bin/deploy.sh /tmp/khaneh-release.tar.gz
#
# It builds from a clean copy of the last commit (not from your working folder), so local files, .env and
# uncommitted changes never reach the server. Works with macOS's bash 3.2.
set -euo pipefail

OUT=${1:-/tmp/khaneh-release.tar.gz}
ROOT=$(git rev-parse --show-toplevel)
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

echo "▶ export of $(git -C "$ROOT" rev-parse --short HEAD)"
git -C "$ROOT" archive --format=tar HEAD | tar -x -C "$TMP"
git -C "$ROOT" rev-parse --short HEAD > "$TMP/REVISION"

cd "$TMP"
echo "▶ composer"
cp .env.example .env    # only so the package scripts can boot Laravel; removed below, never shipped
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
echo "▶ npm"
npm ci --no-audit --no-fund --loglevel=error
npm run build
rm -rf node_modules tests .github .env bootstrap/cache/*.php

echo "▶ archive"
# COPYFILE_DISABLE: no macOS «._» files in the archive
COPYFILE_DISABLE=1 tar --exclude='./storage' --exclude='./.env' --exclude='.git' -czf "$OUT" .
echo "✔ $OUT ($(du -h "$OUT" | cut -f1))"
