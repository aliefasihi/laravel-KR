<?php

/**
 * Khaneh Romantic — One messenger (Telegram or Bale). Bale's bot API is Telegram's, at another address,
 * so one client serves both: sendMessage, sendPhoto, answerCallbackQuery, getFile, setWebhook, getUpdates…
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Telegram can go through a proxy (تنظیمات ← ربات) for servers that can't reach it directly.
 */

namespace Modules\Bot\Support;

use App\Core\Settings\Settings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class Channel
{
    public const NAMES = ['telegram' => 'تلگرام', 'bale' => 'بله'];

    private const BASES = ['telegram' => 'https://api.telegram.org', 'bale' => 'https://tapi.bale.ai'];

    public function __construct(public readonly string $name) {}

    /** @return list<self> the channels with a token */
    public static function all(): array
    {
        return array_values(array_filter(array_map(fn ($n) => new self($n), array_keys(self::NAMES)), fn (self $c) => $c->enabled()));
    }

    public static function get(string $name): ?self
    {
        return isset(self::NAMES[$name]) ? new self($name) : null;
    }

    public function enabled(): bool
    {
        return (bool) Settings::get('bot.'.$this->name.'_enabled', false) && $this->token() !== '';
    }

    public function label(): string
    {
        return self::NAMES[$this->name];
    }

    public function token(): string
    {
        return trim((string) Settings::get('bot.'.$this->name.'_token'));
    }

    /** the secret part of the webhook address (changes with the token) */
    public function secret(): string
    {
        return substr(hash_hmac('sha256', $this->name.'|'.$this->token(), (string) config('app.key')), 0, 32);
    }

    // ── API ────────────────────────────────────────────────────────────

    /** @return array<string, mixed>|null the API's «result», or null on failure */
    public function call(string $method, array $params = [], int $timeout = 15): mixed
    {
        try {
            $response = $this->http($timeout)->post($this->base().'/bot'.$this->token().'/'.$method, $params);
            if ($response->json('ok') === true) {
                return $response->json('result') ?? true;
            }
            Log::warning("Bot {$this->name} {$method} failed: ".mb_substr($response->body(), 0, 200));
        } catch (Throwable $e) {
            Log::warning("Bot {$this->name} {$method} error: ".$e->getMessage());
        }

        return null;
    }

    public function send(string $chatId, string $text, ?array $markup = null): mixed
    {
        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId, 'text' => mb_substr($text, 0, 4000),
            'reply_markup' => $markup ? json_encode($markup, JSON_UNESCAPED_UNICODE) : null,
        ]));
    }

    public function sendPhoto(string $chatId, string $path, string $caption = '', ?array $markup = null): mixed
    {
        try {
            $response = $this->http(30)->attach('photo', (string) file_get_contents($path), basename($path))
                ->post($this->base().'/bot'.$this->token().'/sendPhoto', array_filter([
                    'chat_id' => $chatId, 'caption' => mb_substr($caption, 0, 1000),
                    'reply_markup' => $markup ? json_encode($markup, JSON_UNESCAPED_UNICODE) : null,
                ]));
            if ($response->json('ok') === true) {
                return $response->json('result');
            }
        } catch (Throwable $e) {
            Log::warning("Bot {$this->name} sendPhoto error: ".$e->getMessage());
        }

        return $this->send($chatId, $caption, $markup);   // the text at least
    }

    public function answerCallback(string $id, string $text = ''): void
    {
        $this->call('answerCallbackQuery', array_filter(['callback_query_id' => $id, 'text' => $text]), 5);
    }

    /** downloads a file the user sent; returns its bytes */
    public function download(string $fileId): ?string
    {
        $file = $this->call('getFile', ['file_id' => $fileId]);
        if (! is_array($file) || empty($file['file_path'])) {
            return null;
        }
        try {
            $response = $this->http(60)->get($this->base().'/file/bot'.$this->token().'/'.$file['file_path']);

            return $response->successful() ? $response->body() : null;
        } catch (Throwable $e) {
            Log::warning("Bot {$this->name} download error: ".$e->getMessage());

            return null;
        }
    }

    public function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/bot/hook/'.$this->name.'/'.$this->secret();
    }

    public function setWebhook(): bool
    {
        return (bool) $this->call('setWebhook', ['url' => $this->webhookUrl(), 'allowed_updates' => json_encode(['message', 'callback_query'])]);
    }

    public function deleteWebhook(): bool
    {
        return (bool) $this->call('deleteWebhook');
    }

    /** @return list<array>|null */
    public function updates(int $offset, int $wait = 25): ?array
    {
        $result = $this->call('getUpdates', ['offset' => $offset, 'timeout' => $wait, 'allowed_updates' => json_encode(['message', 'callback_query'])], $wait + 10);

        return is_array($result) ? $result : null;
    }

    /** @return array{username?: string, first_name?: string}|null */
    public function me(): ?array
    {
        $me = $this->call('getMe', [], 10);

        return is_array($me) ? $me : null;
    }

    // ── internals ──────────────────────────────────────────────────────

    private function base(): string
    {
        return self::BASES[$this->name];
    }

    private function http(int $timeout): PendingRequest
    {
        $request = Http::connectTimeout(8)->timeout($timeout)->acceptJson();
        $proxy = $this->name === 'telegram' ? trim((string) Settings::get('bot.telegram_proxy')) : '';

        return $proxy !== '' ? $request->withOptions(['proxy' => $proxy]) : $request;
    }
}
