#!/usr/bin/env bash
# Khaneh Romantic — put an HTTPS certificate issued on the laptop (acme.sh) on the server.
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   bash deploy/scripts/push-cert.sh khanehromantic.ir        # on the Mac, VPN off; «kr» = the server in ~/.ssh/config
#
# For a server that can't reach Let's Encrypt: the certificate is issued on the Mac with a DNS challenge
# (deploy/README.md, «گواهی HTTPS از روی لپ‌تاپ»), then this script copies it over and reloads nginx.
set -euo pipefail

D=${1:?domain, e.g. khanehromantic.ir}
HOST=${2:-kr}
SRC=$HOME/.acme.sh/${D}_ecc
[[ -f $SRC/fullchain.cer && -f $SRC/$D.key ]] || { echo "✖ no certificate for $D in $SRC (acme.sh --issue first)" >&2; exit 1; }

echo "▶ $(openssl x509 -in "$SRC/fullchain.cer" -noout -enddate)"
scp -q "$SRC/fullchain.cer" "$HOST:/tmp/khaneh-fullchain.pem"
scp -q "$SRC/$D.key" "$HOST:/tmp/khaneh-privkey.pem"
ssh -t "$HOST" "sudo sh -c '
    set -e
    install -d -m 755 /etc/letsencrypt/live/$D
    install -m 644 /tmp/khaneh-fullchain.pem /etc/letsencrypt/live/$D/fullchain.pem
    install -m 600 /tmp/khaneh-privkey.pem  /etc/letsencrypt/live/$D/privkey.pem
    rm -f /tmp/khaneh-fullchain.pem /tmp/khaneh-privkey.pem
    nginx -t -q && systemctl reload nginx
    openssl x509 -in /etc/letsencrypt/live/$D/fullchain.pem -noout -enddate
'"
echo "✔ certificate for $D is on the server"
