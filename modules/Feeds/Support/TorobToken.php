<?php

/**
 * Khaneh Romantic — Checks that a product request really comes from Torob
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Torob signs every call with its private key (JWT, EdDSA / Ed25519) — see Torob-Sync
 * «torob_api_token_guide»: the signature must verify with Torob's public key, `exp`/`nbf` must be
 * current and `aud` must be exactly this site's host (with the port when there is one).
 * Plain libsodium (bundled with PHP) — no JWT package needed.
 */

namespace Modules\Feeds\Support;

use RuntimeException;

final class TorobToken
{
    /** Torob's published public key (SubjectPublicKeyInfo, base64). */
    public const PUBLIC_KEY = 'MCowBQYDK2VwAyEAt6Mu4T0pBORY11W+QeM35UsmLO3vsf+6yKpFDEImFk0=';

    /** seconds of clock difference tolerated on exp / nbf */
    private const LEEWAY = 30;

    public function __construct(private ?string $publicKey = null) {}

    /** @throws RuntimeException with a short reason when the token is not acceptable */
    public function verify(?string $token, ?string $version, string $host): array
    {
        if ($version !== null && $version !== '1') {
            throw new RuntimeException('unsupported token version');
        }
        $parts = explode('.', (string) $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('token is missing or malformed');
        }
        [$head, $body, $signature] = $parts;
        $header = json_decode((string) self::decode($head), true);
        $claims = json_decode((string) self::decode($body), true);
        $signature = self::decode($signature);

        if (! is_array($header) || ($header['alg'] ?? null) !== 'EdDSA' || ! is_array($claims)) {
            throw new RuntimeException('token is not an EdDSA JWT');
        }
        if ($signature === null || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached($signature, $head.'.'.$body, $this->key())) {
            throw new RuntimeException('bad signature');
        }

        $now = time();
        if (! isset($claims['exp']) || $now > (int) $claims['exp'] + self::LEEWAY) {
            throw new RuntimeException('token expired');
        }
        if (isset($claims['nbf']) && $now < (int) $claims['nbf'] - self::LEEWAY) {
            throw new RuntimeException('token not valid yet');
        }
        $audience = (array) ($claims['aud'] ?? []);
        if (! in_array(strtolower($host), array_map('strtolower', $audience), true)) {
            throw new RuntimeException('token is for another host');
        }

        return $claims;
    }

    /** The raw 32-byte Ed25519 key (the last 32 bytes of the DER structure). */
    private function key(): string
    {
        $der = base64_decode($this->publicKey ?? self::PUBLIC_KEY, true);
        if ($der === false || strlen($der) < SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new RuntimeException('public key is invalid');
        }

        return substr($der, -SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
    }

    private static function decode(string $part): ?string
    {
        $raw = base64_decode(strtr($part, '-_', '+/').str_repeat('=', (4 - strlen($part) % 4) % 4), true);

        return $raw === false ? null : $raw;
    }
}
