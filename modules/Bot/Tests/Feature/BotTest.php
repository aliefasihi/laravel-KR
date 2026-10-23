<?php

/**
 * Bale + Telegram bot: linking one person in both apps, a product from photos + text step by step
 * (started in Telegram, published from Bale) with its image folder, the AI layer (voice → text →
 * fields), find & change price, new-order alerts to both apps, the webhook, the admin page.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Bot\Filament\Resources\Operators\Pages\ManageBotOperators;
use Modules\Bot\Models\BotDraft;
use Modules\Bot\Models\BotOperator;
use Modules\Bot\Support\Bot;
use Modules\Bot\Support\Channel;
use Modules\Bot\Support\Numbers;
use Modules\Catalog\Models\Product;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Tests\Feature\ShopTestCase;

class BotTest extends ShopTestCase
{
    /** @var list<array{0: string, 1: string, 2: array}> channel, method, params */
    private array $calls = [];

    private int $updateId = 1;

    private BotOperator $sasan;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->withoutDefer();
        Settings::set(['bot.telegram_enabled' => true, 'bot.telegram_token' => 'TG:1', 'bot.bale_enabled' => true, 'bot.bale_token' => 'BL:1']);

        ob_start();
        imagejpeg(imagecreatetruecolor(40, 40));
        $jpeg = ob_get_clean();

        Http::fake(function (Request $request) use ($jpeg) {
            $url = $request->url();
            if (str_contains($url, 'api.example-ai.test')) {
                return str_contains($url, '/audio/transcriptions')
                    ? Http::response(['text' => 'سرویس قابلمه ده پارچه گرانیتی گلدکیش، قیمت هشت میلیون و چهارصد و پنجاه، موجودی سه تا، کد GK-9100'])
                    : Http::response(['choices' => [['message' => ['content' => json_encode([
                        'name' => 'سرویس قابلمه ۱۰ پارچه گرانیتی گلدکیش', 'brand_id' => $this->goldkish->id, 'category_ids' => [$this->cookware->id],
                        'price' => 8450000, 'stock' => 3, 'sku' => 'GK-9100', 'attributes' => ['جنس' => 'گرانیت'],
                        'description' => '<p>سرویسی برای آشپزی هر روز.</p>', 'meta_title' => 'سرویس قابلمه گرانیتی گلدکیش',
                    ])]]], 'usage' => ['prompt_tokens' => 900, 'completion_tokens' => 300]]);
            }
            $channel = str_contains($url, 'tapi.bale.ai') ? 'bale' : 'telegram';
            if (str_contains($url, '/file/bot')) {
                return Http::response($jpeg);
            }
            $method = basename(parse_url($url, PHP_URL_PATH));
            $data = $request->isMultipart()
                ? collect($request->data())->filter(fn ($p) => is_string($p['contents'] ?? null) && ($p['name'] ?? '') !== 'photo')->mapWithKeys(fn ($p) => [$p['name'] => $p['contents']])->all()
                : $request->data();
            $this->calls[] = [$channel, $method, $data];

            return Http::response(['ok' => true, 'result' => match ($method) {
                'getFile' => ['file_path' => 'photos/file_1.jpg'],
                'getMe' => ['username' => $channel === 'bale' ? 'khaneh_bale_bot' : 'khaneh_tg_bot'],
                default => ['message_id' => 1],
            }]);
        });

        $this->sasan = BotOperator::query()->create(['name' => 'ساسان']);
    }

    // ── helpers ────────────────────────────────────────────────────────

    private function send(string $channel, array $message, string $chat = '111'): void
    {
        app(Bot::class)->receive($channel, ['update_id' => $this->updateId++, 'message' => $message + ['chat' => ['id' => $chat, 'type' => 'private'], 'from' => ['first_name' => 'ساسان']]]);
    }

    private function text(string $channel, string $text, string $chat = '111'): void
    {
        $this->send($channel, ['text' => $text], $chat);
    }

    private function press(string $channel, string $data, string $chat = '111'): void
    {
        app(Bot::class)->receive($channel, ['update_id' => $this->updateId++, 'callback_query' => [
            'id' => 'cb'.$this->updateId, 'data' => $data, 'from' => ['first_name' => 'ساسان'], 'message' => ['chat' => ['id' => $chat, 'type' => 'private']],
        ]]);
    }

    /** the last thing the bot said in that app (text or photo caption) + its buttons */
    private function last(string $channel): array
    {
        foreach (array_reverse($this->calls) as [$ch, $method, $params]) {
            if ($ch === $channel && in_array($method, ['sendMessage', 'sendPhoto'], true)) {
                return [$params['text'] ?? $params['caption'] ?? '', json_decode($params['reply_markup'] ?? '[]', true)];
            }
        }

        return ['', []];
    }

    private function button(string $channel, string $label): string
    {
        foreach ($this->last($channel)[1]['inline_keyboard'] ?? [] as $row) {
            foreach ($row as $b) {
                if (str_contains($b['text'], $label)) {
                    return $b['callback_data'];
                }
            }
        }
        $this->fail("no «{$label}» button in: ".json_encode($this->last($channel), JSON_UNESCAPED_UNICODE));
    }

    private function link(): void
    {
        $code = $this->sasan->newLinkCode();
        $this->text('telegram', '/start '.$code);
        $code = $this->sasan->refresh()->newLinkCode();
        $this->text('bale', $code, '222');
        $this->sasan->refresh();
    }

    // ── tests ──────────────────────────────────────────────────────────

    public function test_numbers(): void
    {
        $this->assertSame(8_450_000, Numbers::toman('۸ میلیون و ۴۵۰'));
        $this->assertSame(2_450_000, Numbers::toman('۲ میلیون و ۴۵۰ هزار تومان'));
        $this->assertSame(2_500_000, Numbers::toman('۲٫۵ میلیون'));
        $this->assertSame(2_400_000, Numbers::toman('دو میلیون و چهارصد'));
        $this->assertSame(245_000, Numbers::toman('2,450,000 ریال'));
        $this->assertNull(Numbers::toman('سلام'));
    }

    public function test_linking_one_person_in_both_apps(): void
    {
        $this->text('telegram', 'سلام', '999');
        $this->assertStringContainsString('مخصوص مدیران', $this->last('telegram')[0]);

        $this->link();
        $this->assertSame(['111', '222'], [$this->sasan->telegram_chat_id, $this->sasan->bale_chat_id]);
        $this->assertStringContainsString('تلگرام هم وصل است', $this->last('bale')[0]);
        $this->assertNull($this->sasan->link_code);

        $this->text('telegram', '123456', '999');   // an old/wrong code does nothing
        $this->assertSame(1, BotOperator::query()->whereNotNull('telegram_chat_id')->count());
    }

    public function test_product_step_by_step_across_apps(): void
    {
        ProductAttribute::query()->create(['name' => 'جنس', 'slug' => 'material']);
        $this->link();

        // Telegram: an album of two photos → one answer
        $this->send('telegram', ['photo' => [['file_id' => 'p1', 'file_size' => 10]], 'media_group_id' => 'g1']);
        $before = count($this->calls);
        $this->send('telegram', ['photo' => [['file_id' => 'p2', 'file_size' => 10]], 'media_group_id' => 'g1']);
        $this->assertCount(0, array_filter(array_slice($this->calls, $before), fn ($c) => $c[1] === 'sendMessage'));
        $draft = BotDraft::query()->sole();
        $this->assertCount(2, $draft->photos);

        $this->text('telegram', "نام: سرویس قابلمه ۱۰ پارچه گرانیتی\nقیمت: ۸ میلیون و ۴۵۰\nموجودی: ۵\nجنس: گرانیت");
        $this->assertStringContainsString('برند', $this->last('telegram')[0]);
        $this->press('telegram', $this->button('telegram', 'گلدکیش'));
        $this->assertStringContainsString('دسته', $this->last('telegram')[0]);
        $this->press('telegram', $this->button('telegram', 'آشپزخانه'));      // has sub-categories → opens them
        $this->press('telegram', $this->button('telegram', 'ظروف پخت و پز'));
        $this->assertStringContainsString('کد محصول', $this->last('telegram')[0]);
        $this->press('telegram', $this->button('telegram', 'خودکار'));
        [$preview] = $this->last('telegram');
        $this->assertStringContainsString('۸٬۴۵۰٬۰۰۰ تومان', $preview);
        $this->assertStringContainsString('جنس: گرانیت', $preview);

        // … and finished in Bale
        $this->text('bale', '📝 پیش‌نویس‌ها', '222');
        $this->assertStringContainsString('سرویس قابلمه', json_encode($this->last('bale')[1], JSON_UNESCAPED_UNICODE));
        $this->press('bale', $this->button('bale', 'سرویس قابلمه'), '222');
        $this->press('bale', $this->button('bale', 'انتشار'), '222');
        $this->assertStringContainsString('منتشر شد', $this->last('bale')[0]);

        $product = Product::query()->where('sku', 'GOL-1001')->sole();
        $this->assertSame([Product::STATUS_ACTIVE, $this->goldkish->id, 'GOL-1001'], [$product->status, $product->brand_id, $product->sku]);
        $this->assertSame([84_500_000, 5], [$product->variants->sole()->price, $product->variants->sole()->stock]);
        $this->assertSame([$this->cookware->id], $product->categories->pluck('id')->all());
        $this->assertCount(2, $product->media);
        $this->assertSame('media/products/goldkish/goldkish-gol-1001/goldkish-gol-1001-1.jpg', $product->media->first()->path);
        Storage::disk('public')->assertExists($product->media->first()->path);
        $this->assertSame('سرویس قابلمه ۱۰ پارچه گرانیتی', $product->media->first()->alt);
        $this->assertSame(['گرانیت'], DB::table('product_attribute_values')->join('attribute_values', 'attribute_values.id', '=', 'attribute_value_id')->where('product_id', $product->id)->pluck('value')->all());
        $this->assertSame(['published', 'idle'], [$draft->refresh()->status, $this->sasan->refresh()->state]);
        Storage::disk('local')->assertMissing('bot/drafts/'.$draft->id);
    }

    public function test_ai_fills_the_product_from_a_voice_note(): void
    {
        Settings::set(['ai.enabled' => true, 'ai.api_key' => 'k', 'ai.base_url' => 'https://api.example-ai.test/v1']);
        ProductAttribute::query()->create(['name' => 'جنس', 'slug' => 'material']);
        $this->link();

        $this->send('telegram', ['photo' => [['file_id' => 'p1']]]);
        $this->send('telegram', ['voice' => ['file_id' => 'v1', 'duration' => 7]]);
        [$preview, $markup] = $this->last('telegram');
        $this->assertStringContainsString('سرویس قابلمه ۱۰ پارچه گرانیتی گلدکیش', $preview);
        $this->assertStringContainsString('GK-9100', $preview);
        $this->press('telegram', $this->button('telegram', 'انتشار'));

        $product = Product::query()->where('sku', 'GK-9100')->sole();
        $this->assertSame([84_500_000, 3, 'سرویس قابلمه گرانیتی گلدکیش'], [$product->variants->sole()->price, $product->variants->sole()->stock, $product->meta_title]);
        $this->assertStringContainsString('آشپزی هر روز', $product->description);
        $this->assertSame(2, DB::table('ai_requests')->where('ok', true)->count());   // voice + fields

        // AI down → the bot keeps going step by step
        Settings::set(['ai.base_url' => 'https://down.example-ai.test/v1']);
        Http::fake(['down.example-ai.test/*' => Http::response('', 500)]);
        $this->text('telegram', '➕ محصول جدید');
        $this->send('telegram', ['photo' => [['file_id' => 'p9']], 'caption' => 'گلدان سرامیکی']);
        $this->assertNotSame('', $this->last('telegram')[0]);
    }

    public function test_find_and_change_price(): void
    {
        $this->link();
        $this->text('bale', '🔎 جستجوی محصول', '222');
        $this->text('bale', '3217073', '222');
        $this->assertStringContainsString('قندان سنگی', $this->last('bale')[0]);
        $this->press('bale', $this->button('bale', 'قیمت'), '222');
        $this->text('bale', '۳ میلیون و ۱۰۰', '222');
        $this->assertSame(31_000_000, $this->variant('3217073')->price);
        $this->press('bale', $this->button('bale', 'موجودی'), '222');
        $this->text('bale', '۷', '222');
        $this->assertSame(7, $this->variant('3217073')->stock);

        // plain text when idle: new product or search?
        $this->text('telegram', 'قندان');
        $this->press('telegram', $this->button('telegram', 'جستجو'));
        $this->assertStringContainsString('قندان سنگی', $this->last('telegram')[0]);
    }

    public function test_new_orders_go_to_both_apps(): void
    {
        $this->link();
        $this->calls = [];
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $this->assertStringContainsString('سفارش جدید', $this->last('telegram')[0]);
        $this->assertStringContainsString('سفارش جدید', $this->last('bale')[0]);

        $this->sasan->update(['notify_orders' => false]);
        $this->calls = [];
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $this->assertSame('', $this->last('telegram')[0]);
    }

    public function test_webhook_and_admin(): void
    {
        $channel = Channel::get('bale');
        $this->postJson('/bot/hook/bale/wrong', ['update_id' => 1])->assertNotFound();
        $this->postJson('/bot/hook/bale/'.$channel->secret(), ['update_id' => 77, 'message' => ['text' => 'hi', 'chat' => ['id' => 5, 'type' => 'private']]])->assertOk();
        $this->assertStringContainsString('مخصوص مدیران', $this->last('bale')[0]);
        $this->postJson('/bot/hook/bale/'.$channel->secret(), ['update_id' => 77, 'message' => ['text' => 'hi', 'chat' => ['id' => 5, 'type' => 'private']]]);
        $this->assertSame(1, collect($this->calls)->where(1, 'sendMessage')->count());   // same update twice → once

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('filament.admin.resources.bot.index'))->assertOk()->assertSee('ساسان');
        Livewire::test(ManageBotOperators::class)->callAction('connect');
        $this->assertTrue(collect($this->calls)->contains(fn ($c) => $c[1] === 'setWebhook' && str_contains($c[2]['url'], '/bot/hook/telegram/')));
        Livewire::test(ManageBotOperators::class)->callTableAction('link', $this->sasan);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $this->sasan->refresh()->link_code);
        $this->get('/admin/settings')->assertOk()->assertSee('ربات بله و تلگرام')->assertSee('هوش مصنوعی');
    }
}
