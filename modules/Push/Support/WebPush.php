<?php

/**
 * Khaneh Romantic — Web Push without extra packages: VAPID keys (RFC 8292), message encryption
 * (RFC 8291, aes128gcm) and the request to the browser's push service — with PHP's OpenSSL only.
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Keys: from .env (WEBPUSH_PUBLIC_KEY / WEBPUSH_PRIVATE_KEY, see `php artisan push:keys`), otherwise
 * made once and kept in storage/app/private/webpush.json. Changing the keys ends every subscription.
 */

namespace Modules\Push\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class WebPush
{
    /** DER prefix of a P-256 public key (SubjectPublicKeyInfo) — followed by the 65-byte point */
    private const P256_SPKI = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    private static ?array $keys = null;

    /** @return array{public: string, private: string} public = base64url point (for the browser), private = PEM */
    public static function keys(): array
    {
        if (self::$keys) {
            return self::$keys;
        }
        $public = (string) config('services.webpush.public_key');
        $private = (string) config('services.webpush.private_key');
        if ($public !== '' && $private !== '') {
            return self::$keys = ['public' => $public, 'private' => str_contains($private, 'BEGIN') ? $private : base64_decode($private)];
        }

        $file = storage_path('app/private/webpush.json');
        if (! File::exists($file)) {
            File::ensureDirectoryExists(dirname($file));
            File::put($file, json_encode(self::generateKeys()));
            @chmod($file, 0600);
        }

        return self::$keys = json_decode(File::get($file), true);
    }

    public static function forgetKeys(): void
    {
        self::$keys = null;
    }

    /** @return array{public: string, private: string} */
    public static function generateKeys(): array
    {
        [$key, $point] = self::newKey();
        openssl_pkey_export($key, $pem);

        return ['public' => self::b64($point), 'private' => $pem];
    }

    /**
     * The encrypted body for one subscription (RFC 8291).
     *
     * @param  string  $p256dh  the browser's public key (base64url)
     * @param  string  $auth  the browser's auth secret (base64url)
     */
    public static function encrypt(string $payload, string $p256dh, string $auth, ?string $salt = null, mixed $localKey = null): string
    {
        $uaPublic = self::unb64($p256dh);
        $authSecret = self::unb64($auth);
        if (strlen($uaPublic) !== 65 || strlen($authSecret) < 16) {
            throw new RuntimeException('Invalid subscription keys.');
        }

        [$local, $asPublic] = $localKey ?? self::newKey();
        $shared = openssl_pkey_derive(openssl_pkey_get_public(self::publicPem($uaPublic)), $local, 32);
        if ($shared === false) {
            throw new RuntimeException('ECDH failed.');
        }

        $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0".$uaPublic.$asPublic, $authSecret);
        $salt ??= random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        $cipher = openssl_encrypt($payload."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        return $salt.pack('N', 4096).chr(65).$asPublic.$cipher.$tag;
    }

    /** Authorization header for the push service of this endpoint (VAPID, ES256 JWT valid 12 hours). */
    public static function vapid(string $endpoint, string $subject): string
    {
        $url = parse_url($endpoint);
        $audience = ($url['scheme'] ?? 'https').'://'.($url['host'] ?? '').(isset($url['port']) ? ':'.$url['port'] : '');
        $keys = self::keys();

        $unsigned = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])).'.'
            .self::b64(json_encode(['aud' => $audience, 'exp' => time() + 43200, 'sub' => $subject], JSON_UNESCAPED_SLASHES));
        if (! openssl_sign($unsigned, $der, $keys['private'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('VAPID signing failed.');
        }

        return 'vapid t='.$unsigned.'.'.self::b64(self::derToRaw($der)).', k='.$keys['public'];
    }

    // ── helpers ────────────────────────────────────────────────────────

    /** @return array{0: \OpenSSLAsymmetricKey, 1: string} key + its uncompressed public point (65 bytes) */
    public static function newKey(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($key)['ec'];

        return [$key, "\x04".str_pad($ec['x'], 32, "\0", STR_PAD_LEFT).str_pad($ec['y'], 32, "\0", STR_PAD_LEFT)];
    }

    public static function publicPem(string $point): string
    {
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode(hex2bin(self::P256_SPKI).$point), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    /** ECDSA signature: DER SEQUENCE{r, s} → the 64-byte r‖s JWT wants */
    private static function derToRaw(string $der): string
    {
        $pos = 2 + (ord($der[1]) & 0x80 ? ord($der[1]) & 0x7F : 0);
        $out = '';
        for ($i = 0; $i < 2; $i++) {
            $len = ord($der[$pos + 1]);
            $int = ltrim(substr($der, $pos + 2, $len), "\0");
            $out .= str_pad($int, 32, "\0", STR_PAD_LEFT);
            $pos += 2 + $len;
        }

        return $out;
    }

    public static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function unb64(string $text): string
    {
        $text = strtr(trim($text), '-_', '+/');

        return (string) base64_decode($text.str_repeat('=', (4 - strlen($text) % 4) % 4));
    }
}
