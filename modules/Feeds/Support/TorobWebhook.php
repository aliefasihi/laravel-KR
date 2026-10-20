<?php

/**
 * Khaneh Romantic — Tells Torob right away which products changed (Torob «Product Webhook»)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Saving a product or a variant (price, stock, title…) only notes its id; once a minute the
 * scheduler sends the noted products to https://api.torob.com/update/webhook/v1/ — up to 100 per
 * request and at most 5 requests a minute (Torob allows 20). Torob then re-reads them through
 * the Product API. Needs the webhook token Torob support gives the shop (تنظیمات ← ترب و ایمالز).
 */

namespace Modules\Feeds\Support;

use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Models\Product;
use Throwable;

final class TorobWebhook
{
    public const URL = 'https://api.torob.com/update/webhook/v1/';

    private const KEY = 'feeds.torob.pending';

    public static function enabled(): bool
    {
        return (bool) Settings::get('feeds.torob', true) && filled(Settings::get('feeds.torob_webhook_token'));
    }

    public static function queue(int $productId): void
    {
        if (! self::enabled()) {
            return;
        }
        $pending = Cache::get(self::KEY, []);
        $pending[$productId] = true;
        Cache::forever(self::KEY, $pending);
    }

    /** @return int how many products were sent */
    public static function flush(int $batches = 5): int
    {
        $pending = array_keys(Cache::get(self::KEY, []));
        if ($pending === [] || ! self::enabled()) {
            return 0;
        }

        $sent = [];
        foreach (array_chunk($pending, 100) as $i => $ids) {
            if ($i >= $batches) {
                break;
            }
            $items = Product::query()->whereKey($ids)->get(['id', 'slug'])
                ->map(fn (Product $p) => ['page_url' => $p->url(), 'page_unique' => (string) $p->id])->values()->all();
            try {
                if ($items !== []) {
                    Http::withToken((string) Settings::get('feeds.torob_webhook_token'))->acceptJson()->timeout(10)
                        ->post(self::URL, ['items' => $items])->throw();
                }
                $sent = [...$sent, ...$ids];
            } catch (Throwable $e) {
                Log::warning('Torob webhook failed: '.$e->getMessage());
                break; // keep the rest for the next minute
            }
        }

        $left = array_diff_key(Cache::get(self::KEY, []), array_flip($sent));
        Cache::forever(self::KEY, $left);

        return count($sent);
    }
}
