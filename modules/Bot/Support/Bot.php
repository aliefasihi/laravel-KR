<?php

/**
 * Khaneh Romantic — The bot: one brain for Telegram and Bale
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Every update (from a webhook or from polling) comes here. The person is found by their chat id in
 * either app; their conversation (state, open draft) lives in the database, so they can switch apps
 * any time. Unknown chats can only link themselves with the one-time code from the panel.
 */

namespace Modules\Bot\Support;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Bot\Models\BotDraft;
use Modules\Bot\Models\BotMessage;
use Modules\Bot\Models\BotOperator;
use Throwable;

final class Bot
{
    /** handles one raw update once (the same update id twice is ignored) */
    public function receive(string $channelName, array $update): void
    {
        $channel = Channel::get($channelName);
        if (! $channel || ! $channel->enabled()) {
            return;
        }
        if (isset($update['update_id'])) {
            $fresh = DB::table('bot_updates')->insertOrIgnore(['channel' => $channelName, 'update_id' => (int) $update['update_id'], 'created_at' => now()]);
            if (! $fresh) {
                return;
            }
        }
        $in = Incoming::from($channelName, $update);
        if (! $in || $in->chatId === '') {
            return;
        }
        try {
            $this->handle($channel, $in);
        } catch (Throwable $e) {
            Log::error('Bot error: '.$e->getMessage(), ['exception' => $e]);
            (new Out($channel, $in->chatId))->say('⚠️ خطایی پیش آمد؛ دوباره امتحان کنید.');
        }
    }

    public function handle(Channel $channel, Incoming $in): void
    {
        $op = BotOperator::query()->where($channel->name.'_chat_id', $in->chatId)->where('is_active', true)->first();
        BotMessage::query()->create([
            'operator_id' => $op?->id, 'channel' => $channel->name, 'chat_id' => $in->chatId, 'direction' => 'in',
            'kind' => $in->kind(), 'text' => mb_substr((string) ($in->data ?? $in->text ?? ($in->photo ? '[عکس]' : ($in->voice ? '[ویس]' : ''))), 0, 1000),
        ]);
        if ($in->callbackId) {
            $channel->answerCallback($in->callbackId);
        }
        $out = new Out($channel, $in->chatId, $op?->id);
        if (! $in->private) {
            return;
        }
        if (! $op) {
            $this->link($channel, $in, $out);

            return;
        }
        $op->forceFill(['last_seen_at' => now()])->save();

        $products = new ProductFlow($op, $out);
        $tools = new ProductTools($op, $out);
        $text = trim((string) $in->text);

        // buttons
        if ($in->data !== null) {
            match (true) {
                str_starts_with($in->data, 'd:') => $products->button($in->data),
                str_starts_with($in->data, 'p:') => $tools->button($in->data),
                str_starts_with($in->data, 'dr:') => $this->draftButton($op, $out, $products, $in->data),
                str_starts_with($in->data, 'new:') => $this->newWithText($op, $products, $in),
                $in->data === 'find:last' => $tools->search((string) $op->ctx('last_text')),
                default => null,
            };

            return;
        }

        // the menu and commands work from anywhere
        $command = match (true) {
            in_array($text, ['/start', '/menu', 'منو'], true) => fn () => $out->menu('سلام '.$op->name.' 👋'),
            in_array($text, ['➕ محصول جدید', '/new'], true) => fn () => $products->start(),
            in_array($text, ['📝 پیش‌نویس‌ها', '/drafts'], true) => fn () => $this->drafts($op, $out),
            in_array($text, ['🔎 جستجوی محصول', '/find'], true) => fn () => $tools->askSearch(),
            in_array($text, ['📦 سفارش‌های امروز', '/orders'], true) => fn () => $tools->orders(),
            in_array($text, ['⚠️ موجودی کم', '/stock'], true) => fn () => $tools->lowStock(),
            in_array($text, ['❓ راهنما', '/help'], true) => fn () => $this->help($out),
            in_array($text, ['❌ لغو', '/cancel', 'لغو'], true) => fn () => $this->cancel($op, $out),
            default => null,
        };
        if ($command) {
            $command();

            return;
        }

        match ($op->state) {
            'draft' => $products->input($in),
            'find' => $text !== '' ? $tools->search($text) : $out->say('نام یا کد محصول را بنویسید.'),
            'pedit' => $tools->value($text),
            default => $this->idle($op, $out, $products, $in),
        };
    }

    /** nothing in progress: a photo or a voice starts a new product; text → new product or search? */
    private function idle(BotOperator $op, Out $out, ProductFlow $products, Incoming $in): void
    {
        if ($in->photo || $in->voice) {
            $products->start($in);

            return;
        }
        $text = trim((string) $in->text);
        if ($text === '') {
            $out->menu();

            return;
        }
        $op->goTo('idle', ['last_text' => mb_substr($text, 0, 2000)]);
        $out->say('با این متن چه کنم؟', Out::buttons([[['➕ محصول جدید با این متن', 'new:text'], ['🔎 جستجوی محصول', 'find:last']]]));
    }

    private function newWithText(BotOperator $op, ProductFlow $products, Incoming $in): void
    {
        $text = (string) $op->ctx('last_text');
        $products->start($text !== '' ? new Incoming($in->channel, $in->chatId, $in->name, text: $text) : null);
    }

    private function link(Channel $channel, Incoming $in, Out $out): void
    {
        $code = preg_replace('/\D/', '', PersianText::toLatinDigits((string) $in->text));
        $op = strlen($code) === 6 ? BotOperator::query()->where('link_code', $code)->where('link_expires_at', '>', now())->first() : null;
        if (! $op) {
            $out->say("این ربات مخصوص مدیران فروشگاه است.\nاگر مدیر هستید، کد اتصال را از پنل (ربات ← افزودن فرد) بگیرید و اینجا بفرستید.");

            return;
        }
        BotOperator::query()->where($channel->name.'_chat_id', $in->chatId)->update([$channel->name.'_chat_id' => null]);   // the chat moves to this person
        $op->forceFill([$channel->name.'_chat_id' => $in->chatId, 'link_code' => null, 'link_expires_at' => null, 'is_active' => true])->save();
        $out->operatorId = $op->id;
        $other = $channel->name === 'telegram' ? 'bale' : 'telegram';
        $out->menu('✅ '.$op->name.' عزیز، '.$channel->label().' شما وصل شد.'
            .($op->{$other.'_chat_id'} ? "\n".Channel::NAMES[$other].' هم وصل است؛ هر دو یکی‌اند و پیش‌نویس‌ها در هر دو هست.' : "\nبرای ".Channel::NAMES[$other].' هم یک کد دیگر از پنل بگیرید.'));
    }

    private function drafts(BotOperator $op, Out $out): void
    {
        $drafts = BotDraft::query()->where('operator_id', $op->id)->where('status', 'open')->latest('updated_at')->limit(10)->get();
        if ($drafts->isEmpty()) {
            $out->say('پیش‌نویسی ندارید. «➕ محصول جدید» را بزنید یا مستقیم عکس بفرستید.');

            return;
        }
        $out->say('📝 پیش‌نویس‌های باز (از تلگرام و بله):', Out::buttons($drafts->map(fn (BotDraft $d) => [
            ['📝 '.mb_substr($d->title(), 0, 30).' · '.PersianText::toPersianDigits((string) count((array) $d->photos)).' عکس', 'dr:open:'.$d->id],
            ['🗑', 'dr:del:'.$d->id],
        ])->all()));
    }

    private function draftButton(BotOperator $op, Out $out, ProductFlow $products, string $data): void
    {
        [, $action, $id] = array_pad(explode(':', $data, 3), 3, null);
        $draft = BotDraft::query()->where('operator_id', $op->id)->where('status', 'open')->find((int) $id);
        if (! $draft) {
            $out->say('این پیش‌نویس دیگر باز نیست.');

            return;
        }
        if ($action === 'del') {
            $draft->update(['status' => 'cancelled']);
            Storage::disk('local')->deleteDirectory('bot/drafts/'.$draft->id);
            if ($op->draft_id === $draft->id) {
                $op->forceFill(['draft_id' => null])->save();
                $op->goTo('idle');
            }
            $out->say('🗑 حذف شد.');

            return;
        }
        $products->open($draft);
    }

    private function cancel(BotOperator $op, Out $out): void
    {
        $op->goTo('idle');
        $out->menu($op->draft_id ? 'باشد. پیش‌نویس محصول در «📝 پیش‌نویس‌ها» می‌ماند.' : 'باشد.');
    }

    private function help(Out $out): void
    {
        $ai = ProductAi::available();
        $out->say(implode("\n", [
            '🤖 راهنما',
            '',
            '➕ محصول جدید: عکس‌ها را بفرستید (چندتا با هم هم می‌شود)، بعد اطلاعات را '.($ai ? 'با ویس یا متن آزاد بگویید؛ هوش مصنوعی پر می‌کند.' : 'خط به خط بنویسید (نام: … / قیمت: … / موجودی: …) یا «قدم‌به‌قدم» بزنید.'),
            'هر چیزی که کم باشد را می‌پرسم؛ آخر پیش‌نمایش می‌آید و با «انتشار» محصول، پوشه‌ی عکس‌ها و خود عکس‌ها در سایت ساخته می‌شود.',
            '',
            '📝 پیش‌نویس‌ها: محصول‌های نیمه‌کاره (هم از تلگرام هم از بله).',
            '🔎 جستجو: با نام یا کد؛ قیمت، حراج، موجودی و انتشار را همان‌جا عوض کنید.',
            '📦 سفارش‌های امروز · ⚠️ موجودی کم',
            '❌ لغو: هر کاری را نیمه‌کاره رها می‌کند.',
        ]));
    }
}
