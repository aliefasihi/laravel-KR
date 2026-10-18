<?php

/**
 * Khaneh Romantic — sms.ir (API v1)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * OTP and order messages use «ارسال سریع» templates (پنل sms.ir ← ارسال سریع ← قالب‌ها): a numeric
 * template id whose text has #CODE# style parameters. Plain text needs the store's line number.
 */

namespace Modules\Sms\Drivers;

use Illuminate\Support\Facades\Http;
use Modules\Sms\Support\SmsDriver;
use Modules\Sms\Support\SmsException;

class SmsIrDriver implements SmsDriver
{
    public function __construct(
        private string $apiKey,
        private int $otpTemplateId,
        private string $otpParameter = 'CODE',
        private ?string $lineNumber = null,
    ) {}

    public function sendOtp(string $mobile, string $code): void
    {
        $this->verify($mobile, $this->otpTemplateId, [$this->otpParameter => $code]);
    }

    /** @param  array<string, mixed>  $values  parameter name => value (names as in the template, without #) */
    public function sendTemplate(string $mobile, string $template, array $values): void
    {
        $this->verify($mobile, (int) $template, $values);
    }

    public function sendText(string $mobile, string $text): void
    {
        if (! $this->lineNumber) {
            throw new SmsException('sms.ir: line number is not set');
        }
        $this->post('send/bulk', ['lineNumber' => (int) $this->lineNumber, 'messageText' => $text, 'mobiles' => [$mobile]]);
    }

    private function verify(string $mobile, int $templateId, array $values): void
    {
        $this->post('send/verify', [
            'mobile' => $mobile,
            'templateId' => $templateId,
            'parameters' => array_map(fn ($name) => ['name' => (string) $name, 'value' => (string) $values[$name]], array_keys($values)),
        ]);
    }

    private function post(string $path, array $body): void
    {
        $response = Http::withHeaders(['X-API-KEY' => $this->apiKey, 'Accept' => 'application/json'])
            ->timeout(8)->retry(2, 300, throw: false)
            ->post('https://api.sms.ir/v1/'.$path, $body);

        if (! $response->successful() || (int) $response->json('status') !== 1) {
            throw new SmsException('sms.ir: '.($response->json('message') ?? $response->status()));
        }
    }
}
