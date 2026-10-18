<?php

/**
 * Khaneh Romantic — An SMS provider
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Sms\Support;

interface SmsDriver
{
    /** One-time login code through the provider's verification template (fast lane, no ads filter). */
    public function sendOtp(string $mobile, string $code): void;

    /** A template message with named values, e.g. order status (template = the provider's template name/id). */
    public function sendTemplate(string $mobile, string $template, array $values): void;

    /** Plain text from the store's line. */
    public function sendText(string $mobile, string $text): void;
}
