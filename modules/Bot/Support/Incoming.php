<?php

/**
 * Khaneh Romantic — One update from Telegram or Bale, in the same shape for both
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Support;

final class Incoming
{
    public function __construct(
        public readonly string $channel,
        public readonly string $chatId,
        public readonly string $name = '',
        public readonly ?string $text = null,
        public readonly ?string $photo = null,           // file id of the largest size
        public readonly ?string $voice = null,           // file id (voice note or audio)
        public readonly ?string $data = null,            // inline button data
        public readonly ?string $callbackId = null,
        public readonly ?string $mediaGroup = null,      // several photos sent together
        public readonly bool $private = true,
    ) {}

    public static function from(string $channel, array $update): ?self
    {
        if ($callback = $update['callback_query'] ?? null) {
            $chat = $callback['message']['chat'] ?? $callback['from'] ?? [];

            return new self($channel, (string) ($chat['id'] ?? ''), self::name($callback['from'] ?? []),
                data: (string) ($callback['data'] ?? ''), callbackId: (string) ($callback['id'] ?? ''),
                private: ($chat['type'] ?? 'private') === 'private');
        }
        $message = $update['message'] ?? null;
        if (! $message || empty($message['chat']['id'])) {
            return null;
        }
        $photo = null;
        if (! empty($message['photo'])) {
            $sizes = $message['photo'];
            usort($sizes, fn ($a, $b) => ($a['file_size'] ?? $a['width'] ?? 0) <=> ($b['file_size'] ?? $b['width'] ?? 0));
            $photo = end($sizes)['file_id'] ?? null;
        } elseif (str_starts_with((string) ($message['document']['mime_type'] ?? ''), 'image/')) {
            $photo = $message['document']['file_id'];   // a photo sent «as file» (full quality)
        }

        return new self(
            $channel,
            (string) $message['chat']['id'],
            self::name($message['from'] ?? []),
            text: $message['text'] ?? $message['caption'] ?? null,
            photo: $photo,
            voice: $message['voice']['file_id'] ?? $message['audio']['file_id'] ?? null,
            mediaGroup: isset($message['media_group_id']) ? (string) $message['media_group_id'] : null,
            private: ($message['chat']['type'] ?? 'private') === 'private',
        );
    }

    public function kind(): string
    {
        return match (true) {
            $this->data !== null => 'button',
            $this->photo !== null => 'photo',
            $this->voice !== null => 'voice',
            str_starts_with((string) $this->text, '/') => 'command',
            default => 'text',
        };
    }

    private static function name(array $from): string
    {
        return trim(($from['first_name'] ?? '').' '.($from['last_name'] ?? '')) ?: (string) ($from['username'] ?? '');
    }
}
