<?php

/**
 * Khaneh Romantic — "SMS" into the log (local development, or until a provider is set up)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Sms\Drivers;

use Illuminate\Support\Facades\Log;
use Modules\Sms\Support\SmsDriver;

class LogDriver implements SmsDriver
{
    public function sendOtp(string $mobile, string $code): void
    {
        Log::info("[sms] login code for {$mobile}: {$code}");
    }

    public function sendTemplate(string $mobile, string $template, array $values): void
    {
        Log::info("[sms] {$template} → {$mobile}", $values);
    }

    public function sendText(string $mobile, string $text): void
    {
        Log::info("[sms] → {$mobile}: {$text}");
    }
}
