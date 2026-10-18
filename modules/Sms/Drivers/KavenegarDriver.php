<?php

/**
 * Khaneh Romantic — Kavenegar (kavenegar.com)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * OTP and order messages go through «verify/lookup» templates (پنل کاوه‌نگار ← ارسال سریع/الگو):
 * the template has %token% (and optionally %token2%, %token3%). Plain text needs a sender line.
 */

namespace Modules\Sms\Drivers;

use Illuminate\Support\Facades\Http;
use Modules\Sms\Support\SmsDriver;
use Modules\Sms\Support\SmsException;

class KavenegarDriver implements SmsDriver
{
    public function __construct(
        private string $apiKey,
        private string $otpTemplate,
        private ?string $sender = null,
    ) {}

    public function sendOtp(string $mobile, string $code): void
    {
        $this->call('verify/lookup.json', ['receptor' => $mobile, 'token' => $code, 'template' => $this->otpTemplate]);
    }

    public function sendTemplate(string $mobile, string $template, array $values): void
    {
        $values = array_values($values);
        $params = ['receptor' => $mobile, 'template' => $template, 'token' => $this->token($values[0] ?? '-')];
        if (isset($values[1])) {
            $params['token2'] = $this->token($values[1]);
        }
        if (isset($values[2])) {
            $params['token3'] = $this->token($values[2]);
        }
        foreach (array_slice($values, 3, 2) as $i => $value) {
            $params['token'.(10 * ($i + 1))] = (string) $value; // token10, token20 may contain spaces
        }
        $this->call('verify/lookup.json', $params);
    }

    public function sendText(string $mobile, string $text): void
    {
        $this->call('sms/send.json', array_filter(['receptor' => $mobile, 'message' => $text, 'sender' => $this->sender]));
    }

    /** token, token2, token3 can't contain spaces. */
    private function token(mixed $value): string
    {
        return str_replace(' ', '‌', (string) $value);
    }

    private function call(string $path, array $params): void
    {
        $response = Http::asForm()->timeout(8)->retry(2, 300, throw: false)
            ->post("https://api.kavenegar.com/v1/{$this->apiKey}/{$path}", $params);

        $status = $response->json('return.status');
        if (! $response->successful() || (int) $status !== 200) {
            throw new SmsException('Kavenegar: '.($response->json('return.message') ?? $response->status()));
        }
    }
}
