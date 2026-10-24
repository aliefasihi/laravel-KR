<?php

/**
 * Khaneh Romantic — The site assistant's answers
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Base layer (always, no AI): greetings, order tracking (order number + the mobile it was placed with;
 * logged-in customers see their own orders), shipping, returns, payment, contact, the shop's ready
 * answers, product search with cards, and «talk to a person» (WhatsApp / phone / leave your number —
 * the bot people get told in Bale/Telegram). AI layer (when on): free questions are answered from the
 * shop's own facts, ready answers and the products found for the question — never from imagination;
 * when unsure it hands over to a person. If AI is off or fails, the base layer answers.
 * Anything not understood is marked «بی‌جواب» for the panel, to become a ready answer.
 */

namespace Modules\Chat\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Modules\Ai\Support\Ai;
use Modules\Bot\Support\Notifier;
use Modules\Chat\Models\ChatConversation;
use Modules\Chat\Models\ChatMessage;
use Modules\Crm\Support\Forms;
use Modules\Customer\Support\Mobile;
use Modules\Order\Models\Order;
use Throwable;

final class Assistant
{
    public const QUICK = ['📦 پیگیری سفارش', '🔎 پیدا کردن محصول', '🚚 ارسال و هزینه', '↩️ مرجوعی', '💳 روش‌های پرداخت', '🙋 صحبت با پشتیبان'];

    /** «back to the menu»: the panel's menu button and the chip under answers send this; it also leaves any step half-way (order tracking…) */
    public const MENU = '🏠 منوی اصلی';

    /** @var list<array> replies of this turn */
    private array $replies = [];

    public function __construct(private ChatConversation $conversation) {}

    public static function greeting(): array
    {
        $name = Settings::get('chat.name') ?: 'دستیار '.config('khaneh.store.name');

        return ['role' => 'bot', 'text' => Settings::get('chat.greeting') ?: "سلام! من {$name} هستم 🌸\nسفارش‌تان را پیگیری کنم، محصولی پیدا کنم یا سؤالی دارید؟", 'quick' => self::QUICK];
    }

    /** @return list<array> the bot's replies (already saved) */
    public function reply(string $text): array
    {
        $text = trim(mb_substr($text, 0, 500));
        $this->save('user', $text);

        try {
            $this->think($text);
        } catch (Throwable $e) {
            report($e);
            $this->say('ببخشید، الان مشکلی پیش آمد. دوباره امتحان کنید یا با پشتیبان صحبت کنید.', quick: ['🙋 صحبت با پشتیبان']);
        }
        $this->conversation->forceFill(['last_message_at' => now(), 'messages_count' => $this->conversation->messages()->count()])->save();

        return $this->replies;
    }

    private function think(string $text): void
    {
        $state = $this->conversation->state;

        // back to the menu, from anywhere (also cancels a half-done order lookup)
        if ($text === self::MENU || in_array(Knowledge::norm($text), ['منو', 'منوی اصلی', 'بازگشت', 'برگشت به منو', 'شروع دوباره', 'انصراف', 'لغو'], true)) {
            $this->state(null);
            $this->say('چه کمکی از دستم برمی‌آید؟ یکی را انتخاب کنید یا سؤال‌تان را بنویسید.', quick: self::QUICK, intent: 'menu');

            return;
        }

        // in the middle of something
        if ($state === 'order_number') {
            $this->orderNumber($text);

            return;
        }
        if ($state === 'order_mobile') {
            $this->orderMobile($text);

            return;
        }
        if ($state === 'contact') {
            $this->contactNumber($text);

            return;
        }

        $k = Knowledge::class;
        match (true) {
            $k::has($text, ['📦 پیگیری سفارش', 'پیگیری', 'سفارشم', 'سفارش من', 'کد رهگیری', 'وضعیت سفارش', 'بسته‌ام', 'بستم کی']) => $this->orders(),
            $k::has($text, ['🙋 صحبت با پشتیبان', 'پشتیبان', 'اپراتور', 'کارشناس', 'آدم واقعی', 'زنگ بزنید', 'تماس بگیرید']) => $this->handoff($text),
            $text === '🔎 پیدا کردن محصول' => $this->say('اسم یا نوع محصول را بنویسید؛ مثلاً «قابلمه گرانیتی» یا «سرویس چای‌خوری».'),
            $k::has($text, ['🚚 ارسال و هزینه', 'هزینه ارسال', 'هزینه‌ی ارسال', 'ارسال رایگان', 'چند روزه میرسه', 'روش ارسال', 'تیپاکس', 'پست پیشتاز', 'باربری']) => $this->fact(Knowledge::shipping(), 'shipping'),
            $k::has($text, ['↩️ مرجوعی', 'مرجوع', 'برگشت کالا', 'پس بدم', 'تعویض', 'شکسته رسید', 'خراب رسید']) => $this->fact(Knowledge::returns(), 'returns'),
            $k::has($text, ['💳 روش‌های پرداخت', 'روش پرداخت', 'قسطی', 'اقساط', 'کارت به کارت', 'پرداخت در محل', 'درگاه']) => $this->fact(Knowledge::payment(), 'payment'),
            $k::has($text, ['آدرس', 'تلفن', 'شماره تماس', 'حضوری', 'ساعت کاری', 'کجایید', 'کجاست']) => $this->fact(Knowledge::contact(), 'contact'),
            $this->isSmallTalk($text) => null,
            default => $this->answer($text),
        };
    }

    // ── orders ─────────────────────────────────────────────────────────

    private function orders(): void
    {
        if (! Modules::enabled('Order')) {
            $this->handoff('پیگیری سفارش');

            return;
        }
        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $orders = Order::query()->where('customer_id', $customer->id)->latest('id')->limit(3)->get();
            if ($orders->isNotEmpty()) {
                foreach ($orders as $order) {
                    $this->orderStatus($order);
                }
                $this->say('سفارش دیگری را می‌خواهید؟ شماره‌اش را بنویسید.', quick: ['🙋 صحبت با پشتیبان']);
                $this->state('order_number');

                return;
            }
        }
        $this->state('order_number');
        $this->say('شماره‌ی سفارش را بنویسید (در پیامک ثبت سفارش هست).');
    }

    private function orderNumber(string $text): void
    {
        $number = preg_replace('/\D/', '', PersianText::toLatinDigits($text));
        if (strlen($number) < 3) {
            $this->state(null);
            $this->think($text);   // not a number: maybe a new question

            return;
        }
        $customer = Auth::guard('customer')->user();
        $order = $customer ? Order::query()->where('customer_id', $customer->id)->where('number', $number)->first() : null;
        if ($order) {
            $this->state(null);
            $this->orderStatus($order);

            return;
        }
        $this->state('order_mobile', ['number' => $number]);
        $this->say('برای امنیت، شماره‌ی موبایلی که با آن سفارش داده‌اید را هم بنویسید.');
    }

    private function orderMobile(string $text): void
    {
        $mobile = Mobile::normalize(PersianText::toLatinDigits($text));
        $number = (string) ($this->conversation->context['number'] ?? '');
        $tries = (int) ($this->conversation->context['tries'] ?? 0) + 1;
        $order = $mobile ? Order::query()->where('number', $number)->where('customer_mobile', $mobile)->first() : null;
        if ($order) {
            $this->state(null);
            $this->orderStatus($order);

            return;
        }
        if ($tries >= 3) {
            $this->state(null);
            $this->say('سفارشی با این شماره و موبایل پیدا نکردم.', quick: ['🙋 صحبت با پشتیبان', '📦 پیگیری سفارش']);

            return;
        }
        $this->state('order_mobile', ['number' => $number, 'tries' => $tries]);
        $this->say($mobile ? 'سفارشی با این شماره و موبایل پیدا نکردم؛ شماره‌ها را دوباره بررسی کنید.' : 'شماره‌ی موبایل درست نیست (مثل ۰۹۱۲…).');
    }

    private function orderStatus(object $order): void
    {
        $status = Order::STATUSES[$order->status] ?? $order->status;
        $text = 'سفارش '.$order->displayNumber().': '.$status.' — '.Money::toman((int) $order->total).' تومان';
        if ($order->tracking_code) {
            $text .= "\nکد رهگیری: ".$order->tracking_code;
        }
        if ($order->status === 'pending') {
            $text .= "\nهنوز پرداخت نشده.";
        }
        $links = $order->customer_id && Auth::guard('customer')->id() === $order->customer_id
            ? [['label' => 'جزئیات سفارش', 'url' => route('customer.orders.show', $order, absolute: false)]]
            : [];
        $this->say($text, links: $links, intent: 'order');
    }

    // ── a person ───────────────────────────────────────────────────────

    private function handoff(string $text): void
    {
        $this->conversation->forceFill(['handoff' => true])->save();
        $links = array_values(array_filter([
            Knowledge::whatsapp() ? ['label' => 'گفتگو در واتس‌اپ', 'url' => Knowledge::whatsapp()] : null,
            config('khaneh.store.phone') ? ['label' => 'تماس: '.PersianText::toPersianDigits((string) config('khaneh.store.phone')), 'url' => 'tel:'.config('khaneh.store.phone')] : null,
        ]));
        $this->say('همکارانم کمک‌تان می‌کنند 🙂 در واتس‌اپ پیام بدهید یا تماس بگیرید. اگر می‌خواهید با شما تماس بگیریم، شماره‌ی موبایل‌تان را بنویسید.', links: $links, intent: 'handoff');
        $this->state('contact', ['about' => mb_substr($text, 0, 200)]);
        $this->notifyStaff('🙋 یک مشتری در چت سایت پشتیبان خواست'.($text && ! str_contains($text, 'پشتیبان') ? ': «'.$text.'»' : '.'));
    }

    private function contactNumber(string $text): void
    {
        $mobile = Mobile::normalize(PersianText::toLatinDigits($text));
        if (! $mobile) {
            $this->state(null);
            $this->think($text);

            return;
        }
        $this->conversation->forceFill(['mobile' => $mobile])->save();
        $this->state(null);
        if (Modules::enabled('Crm') && class_exists(Forms::class)) {
            Forms::record('chat_callback', ['mobile' => $mobile, 'about' => (string) ($this->conversation->context['about'] ?? '')], ['mobile' => $mobile]);
        }
        $this->notifyStaff('📞 درخواست تماس از چت سایت: '.PersianText::toPersianDigits($mobile));
        $this->say('ممنون! به‌زودی با '.PersianText::toPersianDigits($mobile).' تماس می‌گیریم.');
    }

    private function notifyStaff(string $text): void
    {
        if (! Settings::get('chat.notify_bot', true) || ! Modules::enabled('Bot') || ! class_exists(Notifier::class)) {
            return;
        }
        $url = rtrim((string) config('app.url'), '/').'/admin/chats/'.$this->conversation->id;
        \Illuminate\Support\defer(fn () => app(Notifier::class)->broadcast($text."\n🛠 ".$url));
    }

    // ── answers ────────────────────────────────────────────────────────

    private function fact(string $fact, string $intent): void
    {
        $fact !== '' ? $this->say($fact, intent: $intent) : $this->handoff($intent);
    }

    private function isSmallTalk(string $text): bool
    {
        $norm = Knowledge::norm($text);
        if (preg_match('/^(سلام|درود|سلام علیکم|hi|hello)( وقت بخیر| خسته نباشید)?$/u', $norm)) {
            $this->say('سلام، خوش آمدید 🌸 چه کمکی از دستم برمی‌آید؟', quick: self::QUICK);

            return true;
        }
        if (preg_match('/^(مرسی|ممنون|ممنونم|سپاس|متشکرم|تشکر|merci|thanks)/u', $norm)) {
            $this->say('خواهش می‌کنم 🙏 سؤال دیگری بود، همین‌جا هستم.');

            return true;
        }

        return false;
    }

    /** a ready answer → AI (if on) → product search → «didn't get it» */
    private function answer(string $text): void
    {
        if ($faq = Knowledge::faq($text)) {
            $faq->increment('hits');
            $this->say($faq->answer, links: $faq->link ? [['label' => 'بیشتر', 'url' => $faq->link]] : [], intent: 'faq');

            return;
        }
        $products = Knowledge::products($text, 6);

        if (Settings::get('chat.ai', true) && Modules::enabled('Ai') && class_exists(Ai::class) && Ai::enabled()) {
            if ($this->ai($text, $products)) {
                return;
            }
        }

        if ($products->isNotEmpty()) {
            $this->say('این‌ها را پیدا کردم:', products: $products->take(4)->map(fn ($p) => Knowledge::card($p))->values()->all(),
                links: [['label' => 'همه‌ی نتیجه‌ها', 'url' => route('catalog.search', ['q' => implode(' ', Knowledge::words($text))], absolute: false)]], intent: 'search');

            return;
        }
        $this->conversation->forceFill(['unanswered' => true])->save();
        $this->say('متوجه نشدم 🙈 یکی از این‌ها را انتخاب کنید، یا با پشتیبان صحبت کنید.', quick: self::QUICK, intent: 'unknown', unanswered: true);
    }

    private function ai(string $text, $products): bool
    {
        $history = $this->conversation->messages()->reorder('id', 'desc')->limit(7)->get()->reverse()->values()
            ->slice(0, -1)   // without the message just saved
            ->map(fn (ChatMessage $m) => ['role' => $m->role === 'user' ? 'user' : 'assistant', 'content' => mb_substr((string) $m->text, 0, 400)])->all();

        $catalog = $products->map(fn ($p) => (function ($c) {
            return 'id '.$c['id'].': '.$c['name'].' — '.($c['price'] ?? 'بدون قیمت').($c['stock'] ? '' : ' (ناموجود)');
        })(Knowledge::card($p)))->implode("\n");
        $faqs = Knowledge::allFaqs()->map(fn ($f) => 'س: '.$f->question."\nج: ".$f->answer)->implode("\n");
        $store = config('khaneh.store.name');

        $system = "تو دستیار فروشگاه اینترنتی «{$store}» هستی (ظروف، لوازم آشپزخانه، سرو و پذیرایی، دکوراتیو). فارسی، صمیمی و کوتاه (حداکثر ۳ جمله) جواب بده.\n"
            ."فقط از اطلاعات زیر استفاده کن؛ قیمت، موجودی، زمان ارسال یا قانونی را از خودت نساز. اگر جواب در اطلاعات نیست یا مشتری شکایت/مشکل دارد، handoff را true کن.\n"
            ."فقط یک JSON بده: {\"answer\": \"…\", \"product_ids\": [شناسه‌ی محصولات مرتبط از فهرست، حداکثر ۴], \"handoff\": true/false}\n\n"
            ."اطلاعات فروشگاه:\n".implode("\n\n", array_filter([Knowledge::shipping(), Knowledge::returns(), Knowledge::payment(), Knowledge::contact()]))
            ."\n\nجواب‌های آماده:\n".($faqs ?: '—')
            ."\n\nمحصولات پیداشده برای این سؤال:\n".($catalog ?: '— (چیزی پیدا نشد)');

        $context = $history ? "گفتگوی قبلی:\n".collect($history)->map(fn ($m) => ($m['role'] === 'user' ? 'مشتری: ' : 'دستیار: ').$m['content'])->implode("\n")."\n\n" : '';
        $data = app(Ai::class)->json($system, $context.'سؤال مشتری: '.$text, [], 'chat.answer');
        if (! is_array($data) || ! is_string($data['answer'] ?? null) || trim($data['answer']) === '') {
            return false;
        }

        $ids = array_map('intval', (array) ($data['product_ids'] ?? []));
        $cards = $products->filter(fn ($p) => in_array($p->id, $ids, true))->take(4)->map(fn ($p) => Knowledge::card($p))->values()->all();
        $handoff = (bool) ($data['handoff'] ?? false);
        if ($handoff) {
            $this->conversation->forceFill(['unanswered' => true])->save();
        }
        $this->say(trim($data['answer']), products: $cards, quick: $handoff ? ['🙋 صحبت با پشتیبان'] : [], intent: 'ai', ai: true, unanswered: $handoff);

        return true;
    }

    // ── helpers ────────────────────────────────────────────────────────

    private function state(?string $state, array $context = []): void
    {
        $this->conversation->forceFill(['state' => $state, 'context' => $context ?: null])->save();
    }

    private function say(string $text, array $products = [], array $quick = [], array $links = [], string $intent = 'info', bool $ai = false, bool $unanswered = false): void
    {
        $meta = array_filter(['products' => $products, 'quick' => $quick, 'links' => $links, 'intent' => $intent, 'ai' => $ai ?: null, 'unanswered' => $unanswered ?: null]);
        $message = $this->save('bot', $text, $meta);
        $this->replies[] = $message->toWidget();
    }

    private function save(string $role, string $text, array $meta = []): ChatMessage
    {
        return $this->conversation->messages()->create(['role' => $role, 'text' => $text, 'meta' => $meta ?: null, 'created_at' => now()]);
    }
}
