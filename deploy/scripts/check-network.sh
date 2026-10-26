#!/usr/bin/env bash
# Khaneh Romantic — which outside services this server can reach (servers in Iran can't reach some of them).
# نویسنده: علی فصیحی (Ali Fasihi)
#
#   bash check-network.sh
#
# Run it on a fresh server before installing, and again whenever a deploy or a module stops working.
# A FAIL is not always a problem: the last column says what it is needed for and what to do instead.
check() {
    local name=$1 url=$2 need=$3 code
    code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 12 "$url" 2>/dev/null || true)
    if [[ $code =~ ^[23] || $code == 401 || $code == 404 || $code == 405 ]]; then
        printf '  \033[32mOK  \033[0m %-28s %s\n' "$name" "$code"
    else
        printf '  \033[31mFAIL\033[0m %-28s %-4s %s\n' "$name" "${code:-000}" "$need"
    fi
}

echo "Packages and code"
check "Ubuntu mirror (apt)"     "$(grep -m1 -oE 'https?://[^ ]+' /etc/apt/sources.list.d/ubuntu.sources 2>/dev/null || echo http://archive.ubuntu.com/ubuntu/)" "apt: change the mirror (guide, section 4)"
check "GitHub"                  "https://github.com"                      "deploy from git → build on the laptop (build-release.sh)"
check "GitHub downloads"        "https://codeload.github.com"             "composer → Packagist mirror or build on the laptop"
check "Packagist"               "https://repo.packagist.org/packages.json" "composer → Packagist mirror or build on the laptop"
check "npm registry"            "https://registry.npmjs.org/vite"         "npm → npm mirror or build on the laptop"
check "Node.js downloads"       "https://nodejs.org/dist/index.json"      "Node.js → download on the laptop and scp it"
check "Let's Encrypt"           "https://acme-v02.api.letsencrypt.org/directory" "HTTPS certificate (required)"
check "Docker Hub"              "https://registry-1.docker.io/v2/"        "expected in Iran → registry mirrors (section 18)"
check "Docker mirror ArvanCloud" "https://docker.arvancloud.ir/v2/"       "try another mirror"

echo "Services the shop uses"
check "ZarinPal"                "https://payment.zarinpal.com"            "online payment"
check "Kavenegar (SMS)"         "https://api.kavenegar.com"               "SMS login codes"
check "sms.ir"                  "https://api.sms.ir"                      "SMS login codes (if this provider is used)"
check "Bale bot"                "https://tapi.bale.ai"                    "Bale bot"
check "Telegram API"            "https://api.telegram.org"                "usually blocked from Iran: use the Bale bot"
check "Web push (Google FCM)"   "https://fcm.googleapis.com"              "push to Chrome/Android may not arrive"
check "Torob"                   "https://torob.com"                       "Torob feed webhook"
check "AvalAI"                  "https://api.avalai.ir"                   "AI features (Iranian gateway)"
check "OpenAI"                  "https://api.openai.com"                  "blocked from Iran: use AvalAI in the panel"
