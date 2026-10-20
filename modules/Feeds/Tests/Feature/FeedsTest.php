<?php

/**
 * Torob Product API v3 and the Emalls web service: signed requests only, the documented request
 * modes and response shape, Toman prices, the change webhook.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Feeds\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Feeds\Support\TorobToken;
use Modules\Feeds\Support\TorobWebhook;
use Tests\TestCase;

class FeedsTest extends TestCase
{
    use RefreshDatabase;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        // a test key pair stands in for Torob's
        $pair = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($pair);
        $der = hex2bin('302a300506032b6570032100').sodium_crypto_sign_publickey($pair);
        $this->app->instance(TorobToken::class, new TorobToken(base64_encode($der)));

        $brand = Brand::create(['name_fa' => 'گلدکیش', 'name_en' => 'Goldkish', 'slug' => 'goldkish']);
        $category = Category::create(['name' => 'ظروف پخت و پز', 'path' => 'cookware']);
        foreach ([['سرویس قابلمه', 'GK-1', 99_000_000, 84_500_000, 5], ['تابه', 'GK-2', 25_000_000, null, 0], ['کتری', 'GK-3', 30_000_000, null, 2]] as [$name, $sku, $price, $sale, $stock]) {
            $product = Product::create(['brand_id' => $brand->id, 'sku' => $sku, 'name' => $name, 'status' => Product::STATUS_ACTIVE]);
            $product->categories()->sync([$category->id => ['is_primary' => true]]);
            $product->variants()->create(['sku' => $sku.'-1', 'price' => $price, 'sale_price' => $sale, 'stock' => $stock]);
        }
        Product::create(['brand_id' => $brand->id, 'sku' => 'GK-9', 'name' => 'پیش‌نویس', 'status' => Product::STATUS_DRAFT]);
    }

    /** the host the test requests go to (Torob's `aud`) */
    private function host(): string
    {
        $url = parse_url(url('/'));

        return $url['host'].(isset($url['port']) ? ':'.$url['port'] : '');
    }

    private function token(array $claims = []): string
    {
        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $head = $b64(json_encode(['alg' => 'EdDSA', 'typ' => 'JWT', 'v' => 1]));
        $body = $b64(json_encode($claims + ['aud' => $this->host(), 'exp' => time() + 300, 'nbf' => time() - 10]));

        return "$head.$body.".$b64(sodium_crypto_sign_detached("$head.$body", $this->secret));
    }

    private function torob(array $body, ?string $token = null)
    {
        return $this->postJson('/torob_api/v3/products', $body, ['X-Torob-Token' => $token ?? $this->token(), 'X-Torob-Token-Version' => '1']);
    }

    public function test_torob_lists_active_products_in_toman(): void
    {
        $json = $this->torob(['page' => 1, 'sort' => 'date_added_desc'])->assertOk()->json();

        $this->assertSame('torob_api_v3', $json['api_version']);
        $this->assertSame([1, 3, 1], [$json['current_page'], $json['total'], $json['max_pages']]);
        $pot = collect($json['products'])->firstWhere('title', 'سرویس قابلمه');
        $this->assertSame(8_450_000, $pot['current_price']);               // Rials → Toman
        $this->assertSame(9_900_000, $pot['old_price']);
        $this->assertTrue($pot['availability']);
        $this->assertSame('ظروف پخت و پز', $pot['category_name']);
        $this->assertStringStartsWith('http', $pot['page_url']);
        $this->assertSame('گلدکیش', $pot['spec']['برند']);
        $this->assertMatchesRegularExpression('/\+03:30$/', $pot['date_added']);

        $pan = collect($json['products'])->firstWhere('title', 'تابه');
        $this->assertFalse($pan['availability']);
        $this->assertSame(0, $pan['current_price']);                         // out of stock: price 0
    }

    public function test_torob_single_products_by_url_or_id_and_cursor_pages(): void
    {
        $product = Product::query()->where('sku', 'GK-3')->first();
        $this->assertSame([(string) $product->id], array_column($this->torob(['page_urls' => [$product->url().'/']])->json('products'), 'page_unique'));
        $this->assertSame('کتری', $this->torob(['page_uniques' => [(string) $product->id]])->json('products.0.title'));
        $this->assertSame([], $this->torob(['page_uniques' => ['999999']])->assertOk()->json('products'));   // removed → empty

        $first = $this->torob(['sort' => 'product_id_desc'])->assertOk()->json();
        $this->assertCount(3, $first['products']);
        $this->assertNull($first['next_cursor']);
    }

    public function test_torob_rejects_bad_requests_and_foreign_tokens(): void
    {
        $this->torob(['page' => 1])->assertStatus(400)->assertJson(['error' => 'sort parameter is not provided']);
        $this->torob([])->assertStatus(400);
        $this->torob(['page' => 0, 'sort' => 'date_added_desc'])->assertStatus(400);
        $this->torob(['page' => 1, 'sort' => 'price'])->assertStatus(400);

        $this->torob(['page' => 1, 'sort' => 'date_added_desc'], $this->token(['aud' => 'other-shop.ir']))->assertStatus(401);
        $this->torob(['page' => 1, 'sort' => 'date_added_desc'], $this->token(['exp' => time() - 600]))->assertStatus(401);
        $this->torob(['page' => 1, 'sort' => 'date_added_desc'], 'not.a.jwt')->assertStatus(401);
        $this->postJson('/torob_api/v3/products', ['page' => 1, 'sort' => 'date_added_desc'])->assertStatus(401);   // no token

        Settings::set(['feeds.torob' => false]);
        $this->torob(['page' => 1, 'sort' => 'date_added_desc'])->assertNotFound();
    }

    public function test_emalls_answers_only_with_a_token_emalls_confirms(): void
    {
        Http::fake(['emalls.ir/*' => fn ($request) => Http::response(
            $request['token'] === 'good' ? ['success' => true, 'message' => 'the token is valid'] : ['success' => false, 'message' => 'invalid'],
        )]);

        $this->post('/emalls/products', ['token' => 'bad'])->assertStatus(401);
        $json = $this->post('/emalls/products', ['token' => 'good', 'page' => 1, 'limit' => 2])->assertOk()->json();
        $this->assertSame([3, 2, '1.3.0'], [$json['count'], $json['max_pages'], $json['Version']]);
        $this->assertCount(2, $json['products']);
        $this->assertContains($json['products'][0]['availability'], ['instock', 'outofstock']);
        Http::assertSent(fn ($r) => $r['shop_domain'] === parse_url(url('/'), PHP_URL_HOST) && $r['version'] === '1.3.0');
    }

    public function test_changes_are_pushed_to_torob_with_the_webhook_token(): void
    {
        Http::fake(['api.torob.com/*' => Http::response(['message' => 'success'])]);
        $this->assertSame(0, TorobWebhook::flush());                         // no token: nothing is noted

        Settings::set(['feeds.torob_webhook_token' => 'secret-token']);
        Product::query()->where('sku', 'GK-1')->first()->variants()->first()->update(['stock' => 1]);
        $this->assertSame(1, TorobWebhook::flush());
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer secret-token') && $r['items'][0]['page_unique'] !== '');
        $this->assertSame(0, TorobWebhook::flush());                         // sent once
    }
}
