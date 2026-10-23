<?php

/**
 * Khaneh Romantic — Replies to the chat a message came from (and keeps them in the shared history)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Support;

use Modules\Bot\Models\BotMessage;

final class Out
{
    public const MENU = [
        ['➕ محصول جدید', '📝 پیش‌نویس‌ها'],
        ['🔎 جستجوی محصول', '📦 سفارش‌های امروز'],
        ['⚠️ موجودی کم', '❓ راهنما'],
    ];

    public function __construct(public readonly Channel $channel, public readonly string $chatId, public ?int $operatorId = null) {}

    public function say(string $text, ?array $markup = null): void
    {
        $this->channel->send($this->chatId, $text, $markup);
        $this->log($text);
    }

    public function photo(string $path, string $caption, ?array $markup = null): void
    {
        $this->channel->sendPhoto($this->chatId, $path, $caption, $markup);
        $this->log($caption, 'photo');
    }

    public function menu(string $text = 'چه کاری انجام بدم؟'): void
    {
        $this->say($text, self::menuKeyboard());
    }

    public static function menuKeyboard(): array
    {
        return ['keyboard' => array_map(fn ($row) => array_map(fn ($t) => ['text' => $t], $row), self::MENU), 'resize_keyboard' => true, 'is_persistent' => true];
    }

    /** @param  list<list<array{0: string, 1: string}>>  $rows  [[label, data], …] per row */
    public static function buttons(array $rows): array
    {
        return ['inline_keyboard' => array_values(array_filter(array_map(
            fn ($row) => array_values(array_map(fn ($b) => ['text' => $b[0], 'callback_data' => $b[1]], array_filter($row))),
            $rows,
        )))];
    }

    private function log(string $text, string $kind = 'text'): void
    {
        BotMessage::query()->create([
            'operator_id' => $this->operatorId, 'channel' => $this->channel->name, 'chat_id' => $this->chatId,
            'direction' => 'out', 'kind' => $kind, 'text' => mb_substr($text, 0, 1000),
        ]);
    }
}
