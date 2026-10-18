<?php

/**
 * Khaneh Romantic — Sends SMS with the provider chosen in the admin settings
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   app(Sms::class)->otp('09121234567', '48213');
 *
 * Provider and keys: پنل ← تنظیمات فروشگاه ← پیامک (env SMS_* are the fallbacks).
 * Errors are logged and re-thrown so the caller can tell the shopper to try again.
 */

namespace Modules\Sms\Support;

use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Drivers\KavenegarDriver;
use Modules\Sms\Drivers\LogDriver;
use Modules\Sms\Drivers\SmsIrDriver;
use Throwable;

class Sms
{
    public const DRIVERS = ['log' => 'بدون ارسال (فقط در لاگ — برای تست)', 'kavenegar' => 'کاوه‌نگار', 'smsir' => 'sms.ir'];

    private ?SmsDriver $driver = null;

    public function driver(): SmsDriver
    {
        return $this->driver ??= match (Settings::get('sms.driver')) {
            'kavenegar' => new KavenegarDriver(
                (string) Settings::get('sms.kavenegar_key'),
                (string) Settings::get('sms.kavenegar_otp_template'),
                Settings::get('sms.kavenegar_sender'),
            ),
            'smsir' => new SmsIrDriver(
                (string) Settings::get('sms.smsir_key'),
                (int) Settings::get('sms.smsir_otp_template'),
                (string) Settings::get('sms.smsir_otp_parameter', 'CODE'),
                Settings::get('sms.smsir_line'),
            ),
            default => new LogDriver,
        };
    }

    /** For tests and custom providers. */
    public function use(SmsDriver $driver): void
    {
        $this->driver = $driver;
    }

    public function otp(string $mobile, string $code): void
    {
        $this->guard(fn () => $this->driver()->sendOtp($mobile, $code), $mobile);
    }

    public function template(string $mobile, string $template, array $values): void
    {
        $this->guard(fn () => $this->driver()->sendTemplate($mobile, $template, $values), $mobile);
    }

    public function text(string $mobile, string $text): void
    {
        $this->guard(fn () => $this->driver()->sendText($mobile, $text), $mobile);
    }

    private function guard(callable $send, string $mobile): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::warning('SMS to '.substr($mobile, 0, 4).'***'.substr($mobile, -3).' failed: '.$e->getMessage());
            throw $e instanceof SmsException ? $e : new SmsException($e->getMessage(), previous: $e);
        }
    }
}
