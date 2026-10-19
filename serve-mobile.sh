#!/usr/bin/env bash
#
# Khaneh Romantic — run the site so phones on the same Wi-Fi / hotspot can open it
# نویسنده: علی فصیحی (Ali Fasihi)
#
# Two ways to run the project locally:
#   php artisan serve          → normal (this computer only, nothing changes)
#   ./serve-mobile.sh          → mobile: http://<this computer's IP>:8002 on any phone on the same network
#
# Options:  ./serve-mobile.sh [port] [ip]      e.g. ./serve-mobile.sh 8003   or   ./serve-mobile.sh 8002 172.20.10.3
#
# Nothing in .env is changed: APP_URL (used for image links) is given to this server only, so when you
# stop it (Ctrl+C) and run `php artisan serve` again, everything is exactly as before.

set -euo pipefail
cd "$(dirname "$0")"

PORT="${1:-8002}"
IP="${2:-}"

bold=$'\033[1m'; green=$'\033[32m'; yellow=$'\033[33m'; red=$'\033[31m'; reset=$'\033[0m'
say()  { printf '%s\n' "$*"; }
warn() { printf '%s%s%s\n' "$yellow" "$*" "$reset"; }
die()  { printf '%s%s%s\n' "$red" "$*" "$reset" >&2; exit 1; }

[[ -f artisan ]] || die "artisan not found — put this file in the project root."
command -v php >/dev/null || die "php not found."

# 1) this computer's address on the local network (Wi-Fi, Ethernet or iPhone hotspot)
if [[ -z "$IP" ]]; then
    if command -v ipconfig >/dev/null 2>&1; then               # macOS
        for iface in en0 en1 en2 en3 en4 en5 en6 en7 en8 bridge100; do
            IP="$(ipconfig getifaddr "$iface" 2>/dev/null || true)"
            [[ -n "$IP" ]] && break
        done
    fi
    if [[ -z "$IP" ]] && command -v hostname >/dev/null 2>&1; then   # Linux
        IP="$(hostname -I 2>/dev/null | awk '{print $1}' || true)"
    fi
    if [[ -z "$IP" ]] && command -v ifconfig >/dev/null 2>&1; then
        IP="$(ifconfig 2>/dev/null | awk '/inet / && $2 != "127.0.0.1" {print $2; exit}' | sed 's/addr://')"
    fi
fi
[[ -n "$IP" ]] || die "Could not find this computer's IP. Connect to Wi-Fi/hotspot, or pass it: ./serve-mobile.sh $PORT 192.168.1.10"
URL="http://$IP:$PORT"

# 2) port must be free
if command -v lsof >/dev/null 2>&1 && lsof -nP -iTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1; then
    die "Port $PORT is busy (is 'php artisan serve' still running?). Stop it, or use another port: ./serve-mobile.sh 8003"
fi

# 3) CSS/JS: phones can't reach the Vite dev server (npm run dev), so use built files
HOT_MOVED=0
if [[ -f public/hot ]]; then
    warn "npm run dev is (or was) running — using the built CSS/JS instead while in mobile mode."
    mv public/hot public/hot.mobile-off
    HOT_MOVED=1
fi
ROOT="$(pwd)"
restore() {
    if [[ "$HOT_MOVED" == 1 && -f "$ROOT/public/hot.mobile-off" ]]; then
        mv "$ROOT/public/hot.mobile-off" "$ROOT/public/hot"
    fi
}
trap restore EXIT
trap 'exit 130' INT TERM

needs_build=0
if [[ ! -f public/build/manifest.json ]]; then
    needs_build=1
elif [[ -n "$(find resources/css resources/js resources/views modules -newer public/build/manifest.json \( -name '*.css' -o -name '*.js' -o -name '*.blade.php' \) -print -quit 2>/dev/null)" ]]; then
    needs_build=1   # Tailwind classes live in the templates too
fi
if [[ "$needs_build" == 1 ]]; then
    command -v npm >/dev/null || die "npm not found — run 'npm run build' once, then try again."
    [[ -d node_modules ]] || { say "Installing npm packages…"; npm install --silent; }
    say "Building CSS/JS (npm run build)…"
    npm run build --silent >/dev/null
fi

# 4) a cached config would ignore the APP_URL below; uploaded images need the storage link
if [[ -f bootstrap/cache/config.php ]]; then
    php artisan config:clear >/dev/null
fi
[[ -e public/storage ]] || php artisan storage:link >/dev/null 2>&1 || true

# 5) run — only this server sees these values; .env stays untouched
say ""
say "${bold}${green}Khaneh Romantic — mobile mode${reset}"
say "  On the phone (same Wi-Fi / hotspot) open:  ${bold}$URL${reset}"
say "  Admin panel:                               $URL/admin"
say "  On this computer:                          http://127.0.0.1:$PORT"
if command -v qrencode >/dev/null 2>&1; then
    say ""; qrencode -t ANSIUTF8 -m 1 "$URL"
else
    say "  (for a QR code to scan: brew install qrencode)"
fi
say ""
say "  Stop with Ctrl+C. Normal mode afterwards: php artisan serve"
say "  macOS may ask to allow incoming connections for php — click Allow."
say ""

export APP_URL="$URL" ASSET_URL="$URL" SESSION_DOMAIN=null SESSION_SECURE_COOKIE=false
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
cd public   # (Laravel's router script serves files from the current directory, like artisan serve)
php -S "0.0.0.0:$PORT" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
