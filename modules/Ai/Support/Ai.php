<?php

/**
 * Khaneh Romantic — AI through any OpenAI-compatible API
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Works with OpenAI itself and with gateways that speak the same API (AvalAI, GapGPT, OpenRouter,
 * a self-hosted server…): base URL + key + model names in تنظیمات ← هوش مصنوعی.
 *   json($system, $user, $images)  → an array (chat completion in JSON mode; images = local paths)
 *   chat($messages)                → text
 *   transcribe($path)              → Persian text of a voice message
 * Every call is short-timed, logged (ai_requests) and never throws: null means «couldn't» and
 * the caller falls back to its step-by-step way.
 */

namespace Modules\Ai\Support;

use App\Core\Settings\Settings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class Ai
{
    public static function enabled(): bool
    {
        return (bool) Settings::get('ai.enabled', false) && filled(Settings::get('ai.api_key'));
    }

    public static function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get('ai.'.$key, $default);
    }

    /**
     * @param  list<string>  $images  local image paths (sent only when «vision» is on)
     * @return array<string, mixed>|null
     */
    public function json(string $system, string $user, array $images = [], string $purpose = 'json'): ?array
    {
        $content = [['type' => 'text', 'text' => $user]];
        if ($images && self::setting('vision', true)) {
            foreach (array_slice($images, 0, 3) as $path) {
                if ($url = self::dataUrl($path)) {
                    $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url, 'detail' => 'low']];
                }
            }
        }

        $text = $this->complete([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => count($content) === 1 ? $user : $content],
        ], $purpose, json: true);
        if ($text === null) {
            return null;
        }
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', $text));
        $data = json_decode($text, true);

        return is_array($data) ? $data : null;
    }

    /** @param  list<array{role: string, content: mixed}>  $messages */
    public function chat(array $messages, string $purpose = 'chat', int $maxTokens = 600): ?string
    {
        return $this->complete($messages, $purpose, maxTokens: $maxTokens);
    }

    public function transcribe(string $path, string $purpose = 'voice'): ?string
    {
        if (! self::enabled() || ! is_file($path)) {
            return null;
        }
        $model = (string) self::setting('stt_model', 'whisper-1');
        $started = microtime(true);
        try {
            $response = $this->client(60)->attach('file', file_get_contents($path), basename($path))
                ->post('/audio/transcriptions', ['model' => $model, 'language' => 'fa', 'response_format' => 'json']);
            $text = $response->successful() ? trim((string) $response->json('text')) : null;
            $this->log($purpose, $model, $started, $text !== null, error: $response->successful() ? null : 'HTTP '.$response->status().' '.mb_substr($response->body(), 0, 150));

            return $text !== '' ? $text : null;
        } catch (Throwable $e) {
            $this->log($purpose, $model, $started, false, error: $e->getMessage());

            return null;
        }
    }

    /** a quick «does it work?» for the settings page */
    public function test(): array
    {
        $started = microtime(true);
        $answer = $this->chat([['role' => 'user', 'content' => 'فقط بنویس: سلام']], 'test', 20);

        return ['ok' => $answer !== null, 'answer' => $answer, 'ms' => (int) ((microtime(true) - $started) * 1000)];
    }

    // ── internals ──────────────────────────────────────────────────────

    private function complete(array $messages, string $purpose, bool $json = false, int $maxTokens = 1500): ?string
    {
        if (! self::enabled()) {
            return null;
        }
        $model = (string) self::setting('chat_model', 'gpt-4o-mini');
        $started = microtime(true);
        try {
            $response = $this->client((int) self::setting('timeout', 45))->post('/chat/completions', array_filter([
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.2,
                'max_tokens' => $maxTokens,
                'response_format' => $json ? ['type' => 'json_object'] : null,
            ]));
            if (! $response->successful()) {
                $this->log($purpose, $model, $started, false, error: 'HTTP '.$response->status().' '.mb_substr($response->body(), 0, 150));

                return null;
            }
            $this->log($purpose, $model, $started, true, (int) $response->json('usage.prompt_tokens'), (int) $response->json('usage.completion_tokens'));

            return (string) $response->json('choices.0.message.content');
        } catch (Throwable $e) {
            $this->log($purpose, $model, $started, false, error: $e->getMessage());

            return null;
        }
    }

    private function client(int $timeout): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) self::setting('base_url', 'https://api.openai.com/v1'), '/'))
            ->withToken((string) self::setting('api_key'))
            ->acceptJson()
            ->connectTimeout(8)
            ->timeout(max(10, $timeout));

        return ($proxy = self::setting('proxy')) ? $request->withOptions(['proxy' => (string) $proxy]) : $request;
    }

    /** a small JPEG data URL (≤ 768 px), so pictures cost few tokens */
    private static function dataUrl(string $path): ?string
    {
        if (! is_file($path) || ! function_exists('imagecreatefromstring')) {
            return null;
        }
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) {
            return null;
        }
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, 768 / max($w, $h));
        if ($scale < 1) {
            $image = imagescale($image, (int) ($w * $scale), (int) ($h * $scale));
        }
        ob_start();
        imagejpeg($image, null, 80);

        return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
    }

    private function log(string $purpose, string $model, float $started, bool $ok, int $in = 0, int $out = 0, ?string $error = null): void
    {
        try {
            DB::table('ai_requests')->insert([
                'purpose' => mb_substr($purpose, 0, 40), 'model' => mb_substr($model, 0, 80), 'ok' => $ok,
                'ms' => (int) ((microtime(true) - $started) * 1000), 'tokens_in' => $in, 'tokens_out' => $out,
                'error' => $error ? mb_substr($error, 0, 250) : null, 'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('AI log failed: '.$e->getMessage());
        }
        if (! $ok) {
            Log::warning("AI {$purpose} failed: {$error}");
        }
    }

    /** @return array{calls: int, failed: int, tokens: int, avg_ms: int} last 30 days */
    public static function usage(): array
    {
        $row = DB::table('ai_requests')->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('count(*) as calls, coalesce(sum(case when ok then 0 else 1 end),0) as failed, coalesce(sum(tokens_in + tokens_out),0) as tokens, coalesce(avg(ms),0) as avg_ms')
            ->first();

        return ['calls' => (int) $row->calls, 'failed' => (int) $row->failed, 'tokens' => (int) $row->tokens, 'avg_ms' => (int) $row->avg_ms];
    }
}
