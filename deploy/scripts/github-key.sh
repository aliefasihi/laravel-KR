#!/usr/bin/env bash
# Khaneh Romantic — read-only access for the server to the private GitHub repository (a deploy key).
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   sudo /srv/khanehromantic/bin/github-key.sh
#
# Makes a key for the site user (khaneh) once and prints its public half. Put it in GitHub:
# repository → Settings → Deploy keys → Add deploy key («Allow write access» stays off), then run this again to test.
# GitHub's SSH is reached over port 443 (ssh.github.com), which networks in Iran rarely block.
set -euo pipefail

APP_DIR=/srv/khanehromantic
APP_USER=khaneh
KEY=$APP_DIR/.ssh/github_deploy

[[ $EUID -eq 0 ]] || { echo "با sudo اجرا کنید: sudo $0" >&2; exit 1; }

install -d -o "$APP_USER" -g "$APP_USER" -m 700 "$APP_DIR/.ssh"
[[ -f $KEY ]] || runuser -u "$APP_USER" -- ssh-keygen -q -t ed25519 -N '' -C "khaneh-server-deploy" -f "$KEY"

cat > "$APP_DIR/.ssh/config" <<EOF
Host github.com
    HostName ssh.github.com
    Port 443
    User git
    IdentityFile $KEY
    IdentitiesOnly yes
EOF
touch "$APP_DIR/.ssh/known_hosts"
grep -q '^\[ssh.github.com\]:443 ' "$APP_DIR/.ssh/known_hosts" || ssh-keyscan -p 443 -t ed25519 ssh.github.com 2>/dev/null >> "$APP_DIR/.ssh/known_hosts"
chown "$APP_USER:$APP_USER" "$APP_DIR/.ssh/config" "$APP_DIR/.ssh/known_hosts"
chmod 600 "$APP_DIR/.ssh/config" "$APP_DIR/.ssh/known_hosts"

if runuser -u "$APP_USER" -- env HOME="$APP_DIR" ssh -T git@github.com 2>&1 | grep -q 'successfully authenticated'; then
    printf '\n\033[1;32m✔ سرور به مخزن گیت‌هاب دسترسی دارد (فقط خواندن).\033[0m\n'
else
    printf '\nاین کلید را در گیت‌هاب بگذارید: مخزن ← Settings ← Deploy keys ← Add deploy key\n'
    printf '(Title: khaneh-server، تیک Allow write access خاموش بماند). بعد همین دستور را دوباره بزنید.\n\n'
    cat "$KEY.pub"
fi
