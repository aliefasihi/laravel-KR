<?php

/**
 * Khaneh Romantic — Web push: subscriptions, what to send to whom, and the sender
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Nothing is sent while a page is being made: messages go to an outbox (push_outbox) and the
 * scheduler (`push:send`, every minute) delivers them to the browsers' push services, 10 at a time,
 * with short timeouts. When the push services can't be reached (international internet cut), the
 * run stops early and the messages wait; after 24 hours they're dropped. A browser that has
 * unsubscribed (404/410) is forgotten.
 */

namespace Modules\Push\Support;

use App\Core\Settings\Settings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Push\Models\PushCampaign;
use Modules\Push\Models\PushSubscription;
use Modules\Pwa\Http\Controllers\PwaController;
use Modules\Pwa\Support\AppIcons;
use Throwable;

final class Push
{
    /** messages older than this are dropped instead of retried */
    private const KEEP_HOURS = 24;

    public static function enabled(): bool
    {
        return (bool) Settings::get('push.enabled', false) && PwaController::enabled();
    }

    // ── subscriptions ──────────────────────────────────────────────────

    /** @param  array{endpoint?: string, keys?: array{p256dh?: string, auth?: string}}  $data */
    public function subscribe(array $data, ?int $customerId = null, ?string $userAgent = null): ?PushSubscription
    {
        $endpoint = (string) ($data['endpoint'] ?? '');
        $p256dh = (string) ($data['keys']['p256dh'] ?? '');
        $auth = (string) ($data['keys']['auth'] ?? '');
        if (! str_starts_with($endpoint, 'https://') || strlen($endpoint) > 2000 || strlen(WebPush::unb64($p256dh)) !== 65 || strlen(WebPush::unb64($auth)) < 16) {
            return null;
        }

        $subscription = PushSubscription::query()->firstOrNew(['endpoint_hash' => hash('sha256', $endpoint)]);
        $subscription->fill([
            'endpoint' => $endpoint, 'p256dh' => $p256dh, 'auth' => $auth, 'failures' => 0,
            'device' => self::device((string) $userAgent),
        ]);
        if ($customerId) {   // a guest subscription on a shared device keeps its last customer
            $subscription->customer_id = $customerId;
        }
        $subscription->save();

        return $subscription;
    }

    public function unsubscribe(string $endpoint): void
    {
        PushSubscription::query()->where('endpoint_hash', hash('sha256', $endpoint))->delete();
    }

    /** The browser replaced its subscription (service worker «pushsubscriptionchange»). */
    public function renew(string $oldEndpoint, array $data): bool
    {
        $old = PushSubscription::query()->where('endpoint_hash', hash('sha256', $oldEndpoint))->first();
        if (! $old) {
            return false;
        }
        $customerId = $old->customer_id;
        $old->delete();

        return (bool) $this->subscribe($data, $customerId);
    }

    // ── what to send ───────────────────────────────────────────────────

    /** @param  array{title: string, body?: ?string, url?: ?string, tag?: ?string, image?: ?string}  $message */
    public function toCustomer(int $customerId, array $message): int
    {
        if (! self::enabled()) {
            return 0;
        }
        $now = now();
        $rows = PushSubscription::query()->where('customer_id', $customerId)->pluck('id')
            ->map(fn ($id) => ['subscription_id' => $id, 'payload' => json_encode(array_filter($message), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'available_at' => $now, 'created_at' => $now])
            ->all();
        DB::table('push_outbox')->insert($rows);

        return count($rows);
    }

    /** Puts a campaign's messages in the outbox. */
    public function launch(PushCampaign $campaign): int
    {
        $query = PushSubscription::query()
            ->when($campaign->audience === 'customers', fn ($q) => $q->whereNotNull('customer_id'))
            ->when($campaign->audience === 'guests', fn ($q) => $q->whereNull('customer_id'));
        $total = 0;
        $now = now();
        $query->select('id')->chunkById(1000, function ($subs) use ($campaign, $now, &$total) {
            DB::table('push_outbox')->insert($subs->map(fn ($s) => [
                'subscription_id' => $s->id, 'campaign_id' => $campaign->id, 'available_at' => $now, 'created_at' => $now,
            ])->all());
            $total += $subs->count();
        });
        $campaign->update(['status' => $total ? 'sending' : 'sent', 'total' => $total, 'sent_at' => $total ? null : now()]);

        return $total;
    }

    // ── the sender ─────────────────────────────────────────────────────

    /** @return array{sent: int, failed: int, waiting: int} */
    public function process(int $seconds = 45, int $batch = 10): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'waiting' => 0];
        if (! self::enabled()) {
            return $result;
        }
        PushCampaign::query()->where('status', 'scheduled')->where('scheduled_at', '<=', now())->get()->each(fn ($c) => $this->launch($c));

        $until = microtime(true) + $seconds;
        $campaigns = [];
        while (microtime(true) < $until) {
            $rows = DB::table('push_outbox')->where('available_at', '<=', now())->orderBy('id')->limit($batch)->get();
            if ($rows->isEmpty()) {
                break;
            }
            $subs = PushSubscription::query()->whereKey($rows->pluck('subscription_id'))->get()->keyBy('id');
            $messages = [];
            foreach ($rows as $row) {
                $campaign = $row->campaign_id ? ($campaigns[$row->campaign_id] ??= PushCampaign::query()->find($row->campaign_id)) : null;
                $payload = $campaign ? $campaign->payload() : (array) json_decode((string) $row->payload, true);
                $messages[$row->id] = [$row, $subs->get($row->subscription_id), $this->withDefaults($payload)];
            }

            $responses = $this->deliver($messages);
            $offline = 0;
            foreach ($messages as $id => [$row, $sub]) {
                $outcome = $this->outcome($responses[$id] ?? null);
                $offline += $outcome === 'offline' ? 1 : 0;
                $this->settle($row, $sub, $outcome, $result);
            }
            if ($offline === count($messages)) {   // nothing reachable: try again next minute
                break;
            }
        }
        foreach ($campaigns as $campaign) {
            if ($campaign && $campaign->status === 'sending' && ! DB::table('push_outbox')->where('campaign_id', $campaign->id)->exists()) {
                $campaign->update(['status' => 'sent', 'sent_at' => now()]);
            }
        }
        $result['waiting'] = DB::table('push_outbox')->count();

        return $result;
    }

    /** @param  array<int, array{0: object, 1: ?PushSubscription, 2: array}>  $messages */
    private function deliver(array $messages): array
    {
        $subject = 'mailto:'.(config('khaneh.store.email') ?: 'info@'.parse_url((string) config('app.url'), PHP_URL_HOST));
        $requests = [];
        foreach ($messages as $id => [, $sub, $payload]) {
            if (! $sub) {
                continue;
            }
            try {
                $requests[$id] = [
                    $sub->endpoint,
                    WebPush::encrypt(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $sub->p256dh, $sub->auth),
                    ['Authorization' => WebPush::vapid($sub->endpoint, $subject), 'Content-Encoding' => 'aes128gcm', 'TTL' => (string) (self::KEEP_HOURS * 3600), 'Urgency' => 'normal'],
                ];
            } catch (Throwable $e) {
                $requests[$id] = null;   // broken keys: forget the subscription
            }
        }

        $send = array_filter($requests);
        $responses = $send === [] ? [] : Http::pool(fn (Pool $pool) => array_map(
            fn ($id) => $pool->as((string) $id)->withHeaders($send[$id][2])->withBody($send[$id][1], 'application/octet-stream')
                ->connectTimeout(4)->timeout(8)->post($send[$id][0]),
            array_keys($send),
        ));

        $out = [];
        foreach ($requests as $id => $request) {
            $out[$id] = $request === null ? 'invalid' : ($responses[(string) $id] ?? null);
        }

        return $out;
    }

    /** sent | gone (unsubscribed: forget it) | retry | offline (push service unreachable) */
    private function outcome(mixed $response): string
    {
        if ($response === 'invalid' || $response === null) {
            return 'gone';
        }
        if ($response instanceof ConnectionException || $response instanceof Throwable) {
            return 'offline';
        }
        if (! $response instanceof Response) {
            return 'retry';
        }

        return match (true) {
            $response->successful() => 'sent',
            in_array($response->status(), [404, 410], true) => 'gone',
            in_array($response->status(), [400, 403, 413], true) => 'gone',   // rejected for good (keys, size)
            default => 'retry',
        };
    }

    private function settle(object $row, ?PushSubscription $sub, string $outcome, array &$result): void
    {
        $campaign = $row->campaign_id ? PushCampaign::query()->whereKey($row->campaign_id) : null;
        if ($outcome === 'sent') {
            DB::table('push_outbox')->where('id', $row->id)->delete();
            $sub?->forceFill(['last_sent_at' => now(), 'failures' => 0])->save();
            $campaign?->increment('delivered');
            $result['sent']++;

            return;
        }
        if ($outcome === 'gone') {
            $sub ? $sub->delete() : DB::table('push_outbox')->where('id', $row->id)->delete();   // cascades to its outbox rows
            $campaign?->increment('failed');
            $result['failed']++;

            return;
        }
        // retry / offline: later, unless it's too old
        if (now()->diffInHours($row->created_at, true) >= self::KEEP_HOURS || $row->attempts >= 20) {
            DB::table('push_outbox')->where('id', $row->id)->delete();
            $campaign?->increment('failed');
            $result['failed']++;

            return;
        }
        DB::table('push_outbox')->where('id', $row->id)->update([
            'attempts' => $row->attempts + 1,
            'available_at' => now()->addMinutes(min(60, 5 * ($row->attempts + 1))),
        ]);
        if ($outcome === 'retry') {
            Log::info('Push delivery deferred for subscription '.$row->subscription_id);
        }
    }

    private function withDefaults(array $payload): array
    {
        $icons = AppIcons::urls();

        return $payload + ['icon' => $icons['192'] ?? null, 'url' => '/'];
    }

    private static function device(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'ios',
            (bool) preg_match('/Android/i', $ua) => 'android',
            default => 'desktop',
        };
    }
}
