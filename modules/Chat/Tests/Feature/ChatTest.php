<?php

/**
 * Site chat: the button (instead of WhatsApp, not on checkout), greeting/history, order tracking
 * (guest: number + mobile; customer: own orders), facts from the shop (shipping…), ready answers,
 * product cards, «didn't get it» → unanswered → a ready answer from the panel, a person (call back),
 * the AI layer and its fallback.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Chat\Filament\Resources\Conversations\Pages\ViewChatConversation;
use Modules\Chat\Models\ChatConversation;
use Modules\Chat\Models\ChatFaq;
use Modules\Chat\Support\Assistant;
use Modules\Crm\Models\FormSubmission;
use Modules\Order\Livewire\Checkout;
use Modules\Order\Models\Order;
use Modules\Order\Tests\Feature\ShopTestCase;

class ChatTest extends ShopTestCase
{
    private ?string $token = null;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::set(['chat.enabled' => true]);
        Auth::guard('customer')->logout();
    }

    /** @return list<array> the bot's replies */
    private function say(string $text): array
    {
        $response = $this->postJson(route('chat.send'), ['text' => $text, 'token' => $this->token, 'page' => '/'])->assertOk();
        $this->token = $response->json('token');

        return $response->json('messages');
    }

    private function text(array $replies): string
    {
        return implode("\n", array_column($replies, 'text'));
    }

    public function test_button_and_greeting(): void
    {
        $this->get('/')->assertSee('chat-launcher', false)->assertDontSee('aria-label="پشتیبانی در واتس‌اپ"', false);
        $this->getJson(route('chat.history'))->assertOk()->assertJsonPath('messages.0.role', 'bot')->assertJsonCount(6, 'messages.0.quick');

        Settings::set(['chat.enabled' => false]);
        $this->get('/')->assertDontSee('chat-launcher', false)->assertSee('aria-label="پشتیبانی در واتس‌اپ"', false);
        $this->postJson(route('chat.send'), ['text' => 'سلام'])->assertNotFound();
    }

    public function test_order_tracking(): void
    {
        Auth::guard('customer')->login($this->customer);
        $this->fillCart();
        Livewire::test(Checkout::class)->set('shippingMethod', 'post')->set('paymentMethod', 'cod')->call('place');
        $order = Order::query()->sole();
        Auth::guard('customer')->logout();

        $this->assertStringContainsString('شماره‌ی سفارش', $this->text($this->say('📦 پیگیری سفارش')));
        $this->assertStringContainsString('موبایلی', $this->text($this->say((string) $order->number)));
        $this->assertStringContainsString('پیدا نکردم', $this->text($this->say('09350000000')));
        $this->assertStringContainsString('در حال پردازش', $this->text($this->say('۰۹۱۲۱۲۳۴۵۶۷')));

        // a logged-in customer sees their orders at once, with a link
        Auth::guard('customer')->login($this->customer);
        $this->token = null;
        $replies = $this->say('سفارشم کجاست؟');
        $this->assertStringContainsString('سفارش '.$order->displayNumber(), $this->text($replies));
        $this->assertSame('جزئیات سفارش', $replies[0]['links'][0]['label']);
    }

    public function test_back_to_the_menu_from_anywhere(): void
    {
        $this->get('/')->assertSee('data-chat-menu', false);

        $this->say('📦 پیگیری سفارش');                                 // now waiting for an order number…
        $replies = $this->say(Assistant::MENU);                          // …«منو» leaves that step
        $this->assertSame(Assistant::QUICK, $replies[0]['quick']);
        $this->assertNull(ChatConversation::query()->sole()->state);

        $this->assertSame(Assistant::QUICK, $this->say('بازگشت')[0]['quick']);   // typed, too
    }

    public function test_facts_ready_answers_products_and_unanswered(): void
    {
        $this->assertStringContainsString('پست پیشتاز', $this->text($this->say('هزینه ارسال چقدره؟')));
        $this->assertStringContainsString('خواهش می‌کنم', $this->text($this->say('ممنون')));

        ChatFaq::query()->create(['question' => 'بسته‌بندی کادو دارید؟', 'answer' => 'بله، در سبد خرید «بسته‌بندی کادو» را بزنید.', 'keywords' => ['کادو']]);
        $this->assertStringContainsString('بسته‌بندی کادو', $this->text($this->say('میشه کادو پیچش کنید')));
        $this->assertSame(1, ChatFaq::query()->value('hits'));

        $replies = $this->say('قندان سنگی دارید؟');
        $this->assertSame('قندان سنگی طوسی مدل بلوط', $replies[0]['products'][0]['name']);
        $this->assertSame('۲٬۹۵۰٬۰۰۰ تومان', $replies[0]['products'][0]['price']);

        $this->assertStringContainsString('متوجه نشدم', $this->text($this->say('زژژژ ققق')));
        $conversation = ChatConversation::query()->where('token', $this->token)->sole();
        $this->assertTrue($conversation->unanswered);

        // the panel turns it into a ready answer
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('filament.admin.resources.chats.index'))->assertOk()->assertSee('هزینه ارسال');
        $this->get(route('filament.admin.resources.chats.view', $conversation))->assertOk()->assertSee('قندان سنگی دارید؟');
        Livewire::test(ViewChatConversation::class, ['record' => $conversation->id])
            ->mountAction('faq')->assertActionDataSet(['question' => 'زژژژ ققق'])
            ->setActionData(['answer' => 'جواب تازه'])->callMountedAction()->assertHasNoActionErrors();
        $this->assertFalse($conversation->refresh()->unanswered);
        $this->assertTrue(ChatFaq::query()->where('question', 'زژژژ ققق')->exists());
    }

    public function test_a_person_and_call_back(): void
    {
        $replies = $this->say('🙋 صحبت با پشتیبان');
        $this->assertStringStartsWith('https://wa.me/', $replies[0]['links'][0]['url']);
        $this->assertStringContainsString('تماس می‌گیریم', $this->text($this->say('۰۹۳۵۱۱۱۲۲۳۳')));
        $conversation = ChatConversation::query()->sole();
        $this->assertSame([true, '09351112233'], [$conversation->handoff, $conversation->mobile]);
        $this->assertSame('chat_callback', FormSubmission::query()->sole()->form);
    }

    public function test_ai_answers_from_the_shop_and_falls_back(): void
    {
        Settings::set(['ai.enabled' => true, 'ai.api_key' => 'k', 'ai.base_url' => 'https://api.example-ai.test/v1']);
        $dish = $this->variant('3217073')->product_id;
        Http::fake(['api.example-ai.test/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => json_encode([
                'answer' => 'برای پذیرایی، این قندان سنگی خیلی شیک است.', 'product_ids' => [$dish], 'handoff' => false,
            ])]]]])
            ->push('', 500)]);

        $replies = $this->say('یه چیز شیک برای پذیرایی عید می‌خوام، قندان بهتره؟');
        $this->assertSame('برای پذیرایی، این قندان سنگی خیلی شیک است.', $replies[0]['text']);
        $this->assertSame($dish, $replies[0]['products'][0]['id']);
        $request = Http::recorded()[0][0];
        $this->assertStringContainsString('پست پیشتاز', json_encode($request->data(), JSON_UNESCAPED_UNICODE));   // grounded in shop facts

        // AI down → the base layer still answers
        $this->assertSame('قندان سنگی طوسی مدل بلوط', $this->say('قندان')[0]['products'][0]['name'] ?? null);
        $this->assertSame(1, DB::table('ai_requests')->where('ok', false)->count());
    }
}
