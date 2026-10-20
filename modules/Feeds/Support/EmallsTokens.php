<?php

/**
 * Khaneh Romantic — Is this Emalls token real? (asks emalls.ir, remembers a yes for an hour)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Same check as Emalls' official WooCommerce plugin (v1.3.0): POST token + shop_domain + version to
 * emalls.ir/swservice/wp_plugin.ashx; only {"success": true, "message": "the token is valid"} passes.
 * If emalls.ir can't be reached the answer is no (fails closed). Tokens are never logged.
 */

namespace Modules\Feeds\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class EmallsTokens
{
    public const VERSION = '1.3.0';

    public const VALIDATION_URL = 'https://emalls.ir/swservice/wp_plugin.ashx';

    /** @return array{0: bool, 1: bool} [valid, checked just now] */
    public function check(string $token, string $domain): array
    {
        $key = 'feeds.emalls.token.'.hash('sha256', $token.'|'.$domain);
        if (Cache::get($key) === true) {
            return [true, false];
        }

        try {
            $response = Http::asForm()->timeout(8)->connectTimeout(5)
                ->post(self::VALIDATION_URL, ['token' => $token, 'shop_domain' => $domain, 'version' => self::VERSION]);
            $data = $response->json();
        } catch (Throwable) {
            return [false, true];
        }

        $valid = $response->successful() && is_array($data)
            && (($data['success'] ?? $data['Success'] ?? null) === true)
            && strtolower(trim((string) ($data['message'] ?? $data['Message'] ?? ''))) === 'the token is valid';
        if ($valid) {
            Cache::put($key, true, 3600);
        }

        return [$valid, true];
    }
}
