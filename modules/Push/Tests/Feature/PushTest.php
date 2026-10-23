<?php

/**
 * Web push: the encryption/VAPID a browser can read, subscribing (tied to the customer), order
 * updates and campaigns through the outbox, delivery outcomes (sent / unsubscribed / unreachable),
 * click tracking, the service worker part, admin pages.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Support\OrderWorkflow;
use Modules\Order\Tests\Feature\ShopTestCase;
use Modules\Push\Filament\Resources\Campaigns\Pages\ListPushCampaigns;
use Modules\Push\Models\PushCampaign;
use Modules\Push\Models\PushSubscription;
use Modules\Push\Support\Push;
use Modules\Push\Support\WebPush;

class PushTest extends ShopTestCase
{
    /** @var array{0: \OpenSSLAsymmetricKey, 1: string, 2: string} browser key, its public point, auth secret */
    private array $browser;

    protected function setUp(): void
    {
        parent::setUp();
        $keys = WebPush::generateKeys();
        config(['services.webpush.public_key' => $keys['public'], 'services.webpush.private_key' => $keys['private']]);
        WebPush::forgetKeys();
        Settings::set(['push.enabled' => true, 'pwa.enabled' => true]);
        [$key, $point] = WebPush::newKey();
        $this->browser = [$key, $point, random_bytes(16)];
    }

    private function subscription(string $endpoint): array
    {
        return ['endpoint' => $endpoint, 'keys' => ['p256dh' => WebPush::b64($this->browser[1]), 'auth' => WebPush::b64($this->browser[2])]];
    }

    /** what the browser does with the body (RFC 8291, receiving side) */
    private function decrypt(string $body): string
    {
        [$key, $uaPublic, $auth] = $this->browser;
        $salt = substr($body, 0, 16);
        $asPublic = substr($body, 21, 65);
        $shared = openssl_pkey_derive(openssl_pkey_get_public(WebPush::publicPem($asPublic)), $key, 32);
        $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0".$uaPublic.$asPublic, $auth);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
        $data = substr($body, 86);
        $plain = openssl_decrypt(substr($data, 0, -16), 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, substr($data, -16));

        return rtrim((string) $plain, "\x02");
    }

    public function test_encryption_and_vapid(): void
    {
        $body = WebPush::encrypt('{"title":"سلام"}', WebPush::b64($this->browser[1]), WebPush::b64($this->browser[2]));
        $this->assertSame(4096, unpack('N', substr($body, 16, 4))[1]);
        $this->assertSame('{"title":"سلام"}', $this->decrypt($body));

        $header = WebPush::vapid('https://fcm.googleapis.com/fcm/send/xyz', 'mailto:info@example.com');
        $this->assertMatchesRegularExpression('/^vapid t=[\w-]+\.[\w-]+\.[\w-]+, k=[\w-]+$/', $header);
        preg_match('/t=([^,]+), k=(.+)$/', $header, $m);
        [$h, $c, $sig] = explode('.', $m[1]);
        $this->assertSame('https://fcm.googleapis.com', json_decode(WebPush::unb64($c), true)['aud']);
        $raw = WebPush::unb64($sig);
        $int = fn (string $v) => (ord($v[0]) & 0x80 ? "\0".$v : $v);
        [$r, $s] = [$int(ltrim(substr($raw, 0, 32), "\0")), $int(ltrim(substr($raw, 32), "\0"))];
        $seq = "\x02".chr(strlen($r)).$r."\x02".chr(strlen($s)).$s;
        $der = "\x30".chr(strlen($seq)).$seq;
        $this->assertSame(1, openssl_verify("$h.$c", $der, WebPush::publicPem(WebPush::unb64($m[2])), OPENSSL_ALGO_SHA256));
    }

    public function test_subscribe_and_page_parts(): void
    {
        $this->get('/')->assertSee('push-config', false)->assertSee(WebPush::keys()['public']);
        $this->get('/sw.js')->assertSee('notificationclick', false)->assertSee('pushsubscriptionchange', false);

        $this->postJson(route('push.subscribe'), ['subscription' => ['endpoint' => 'http://insecure']])->assertStatus(422);
        $this->postJson(route('push.subscribe'), ['subscription' => $this->subscription('https://push.example/a')], ['User-Agent' => 'Mozilla/5.0 (Linux; Android 14)'])->assertOk();
        $sub = PushSubscription::query()->sole();
        $this->assertSame([$this->customer->id, 'android'], [$sub->customer_id, $sub->device]);

        // renewed by the service worker (no session / CSRF), keeps the customer
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->postJson('/push/renew', ['old_endpoint' => 'https://push.example/a', 'subscription' => $this->subscription('https://push.example/b')])->assertOk();
        $this->assertSame(['https://push.example/b', $this->customer->id], [PushSubscription::query()->sole()->endpoint, PushSubscription::query()->sole()->customer_id]);
        $this->postJson(route('push.unsubscribe'), ['endpoint' => 'https://push.example/b'])->assertOk();
        $this->assertSame(0, PushSubscription::query()->count());

        Settings::set(['push.enabled' => false]);
        $this->get('/')->assertDontSee('push-config', false);
        $this->get('/sw.js')->assertDontSee('notificationclick', false);
        $this->postJson(route('push.subscribe'), ['subscription' => $this->subscription('https://push.example/c')])->assertNotFound();
    }

    public function test_order_updates_and_delivery(): void
    {
        app(Push::class)->subscribe($this->subscription('https://push.example/ok/1'), $this->customer->id);
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $order = Order::query()->sole();
        $this->get(route('customer.orders.show', $order))->assertSee('خبر ارسال سفارش با نوتیفیکیشن');

        app(OrderWorkflow::class)->ship($order, 'TR123');
        $row = DB::table('push_outbox')->latest('id')->first();
        $payload = json_decode($row->payload, true);
        $this->assertStringContainsString('ارسال شد', $payload['title']);
        $this->assertStringContainsString('TR123', $payload['body']);
        $this->assertStringStartsWith('/account/orders/'.$order->id, $payload['url']);

        $bodies = [];
        Http::fake(function ($request) use (&$bodies) {
            $bodies[] = $request;

            return Http::response('', 201);
        });
        $result = app(Push::class)->process(5);
        $this->assertSame(0, DB::table('push_outbox')->count());
        $this->assertGreaterThanOrEqual(1, $result['sent']);
        $sent = end($bodies);
        $this->assertSame('aes128gcm', $sent->header('Content-Encoding')[0]);
        $this->assertStringStartsWith('vapid t=', $sent->header('Authorization')[0]);
        $this->assertStringContainsString('TR123', $this->decrypt($sent->body()));
    }

    public function test_campaign_outcomes_and_clicks(): void
    {
        $push = app(Push::class);
        $push->subscribe($this->subscription('https://push.example/ok/1'), $this->customer->id);
        $push->subscribe($this->subscription('https://push.example/gone/2'));
        $push->subscribe($this->subscription('https://push.example/down/3'));
        $campaign = PushCampaign::query()->create(['title' => 'حراج یلدا', 'body' => 'تا ۳۰٪', 'url' => '/shop?sale=1']);

        $this->assertSame(3, $push->launch($campaign));
        Http::fake([
            'push.example/ok/*' => Http::response('', 201),
            'push.example/gone/*' => Http::response('', 410),
            'push.example/down/*' => Http::failedConnection(),
        ]);
        $push->process(5);

        $campaign->refresh();
        $this->assertSame(['sending', 1, 1], [$campaign->status, $campaign->delivered, $campaign->failed]);
        $this->assertSame(2, PushSubscription::query()->count());                    // the unsubscribed one is gone
        $waiting = DB::table('push_outbox')->sole();                                // the unreachable one waits
        $this->assertSame(1, $waiting->attempts);
        $this->assertTrue(now()->lt($waiting->available_at));

        // a day later it's dropped and the campaign is done
        DB::table('push_outbox')->update(['created_at' => now()->subDay(), 'available_at' => now()->subMinute()]);
        $push->process(5);
        $this->assertSame(['sent', 1, 2], [$campaign->refresh()->status, $campaign->delivered, $campaign->failed]);

        // clicks: counted, tagged, only this site
        $this->get(route('push.click', $campaign))->assertRedirect('/shop?sale=1&utm_source=push&utm_campaign=push-'.$campaign->id);
        $campaign->update(['url' => 'https://evil.example/x']);
        $this->get(route('push.click', $campaign))->assertRedirect('/?utm_source=push&utm_campaign=push-'.$campaign->id);
        $this->assertSame(2, $campaign->refresh()->clicks);

        // scheduled campaigns start when due
        $later = PushCampaign::query()->create(['title' => 'بعداً', 'status' => 'scheduled', 'scheduled_at' => now()->subMinute()]);
        PushSubscription::query()->where('endpoint', 'like', '%/down/%')->delete();
        $push->process(5);
        $this->assertSame(['sent', 1, 1], [$later->refresh()->status, $later->total, $later->delivered]);
    }

    public function test_admin_pages(): void
    {
        app(Push::class)->subscribe($this->subscription('https://push.example/ok/1'), $this->customer->id);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('filament.admin.resources.push.index'))->assertOk()->assertSee('۱ مرورگر عضو', false);
        $this->get(route('filament.admin.resources.push.create'))->assertOk();
        $this->get('/admin/settings')->assertOk()->assertSee('نوتیفیکیشن');

        Livewire::test(ListPushCampaigns::class)->callAction('test', ['mobile' => '۰۹۱۲۱۲۳۴۵۶۷', 'title' => 'سلام'])->assertHasNoActionErrors();
        $this->assertSame('سلام', json_decode(DB::table('push_outbox')->sole()->payload, true)['title']);

        $campaign = PushCampaign::query()->create(['title' => 'حراج']);
        Livewire::test(ListPushCampaigns::class)->callTableAction('send', $campaign);
        $this->assertSame(['sending', 1], [$campaign->refresh()->status, $campaign->total]);
    }
}
