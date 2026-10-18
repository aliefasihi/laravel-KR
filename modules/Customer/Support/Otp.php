<?php

/**
 * Khaneh Romantic — One-time login codes by SMS
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *  - 5-digit code, valid 2 minutes, only the newest code of a number counts
 *  - stored as an HMAC (never in plain text), 5 wrong tries burn the code
 *  - «ارسال دوباره» after 60 s; at most 5 codes per number and 20 per IP per hour
 */

namespace Modules\Customer\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Sms\Support\Sms;

class Otp
{
    public const SENT = 'sent';

    public const WAIT = 'wait';           // asked again before resend_after

    public const LIMITED = 'limited';     // hourly limit (number or IP)

    public const FAILED = 'failed';       // SMS provider error

    public const OK = 'ok';

    public const WRONG = 'wrong';

    public const EXPIRED = 'expired';     // expired, burnt or never sent

    /** @return array{status: string, retry_in: int} */
    public function send(string $mobile, ?string $ip = null): array
    {
        $config = config('modules.customer.otp');

        $last = DB::table('customer_otps')->where('mobile', $mobile)->latest('id')->first();
        if ($last && ($elapsed = now()->getTimestamp() - strtotime($last->created_at)) < $config['resend_after']) {
            return ['status' => self::WAIT, 'retry_in' => $config['resend_after'] - $elapsed];
        }

        foreach (['otp:mobile:'.$mobile => $config['per_mobile_hour'], 'otp:ip:'.$ip => $config['per_ip_hour']] as $key => $max) {
            if ($ip === null && str_starts_with($key, 'otp:ip:')) {
                continue;
            }
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return ['status' => self::LIMITED, 'retry_in' => RateLimiter::availableIn($key)];
            }
        }

        $code = $this->generate($config['length']);

        try {
            app(Sms::class)->otp($mobile, $code);
        } catch (\Throwable) {
            return ['status' => self::FAILED, 'retry_in' => 0];
        }

        RateLimiter::hit('otp:mobile:'.$mobile, 3600);
        if ($ip !== null) {
            RateLimiter::hit('otp:ip:'.$ip, 3600);
        }

        DB::table('customer_otps')->insert([
            'mobile' => $mobile,
            'code_hash' => $this->hash($mobile, $code),
            'ip' => $ip,
            'expires_at' => now()->addSeconds($config['ttl']),
            'created_at' => now(),
        ]);

        return ['status' => self::SENT, 'retry_in' => $config['resend_after']];
    }

    public function verify(string $mobile, string $code): string
    {
        $otp = DB::table('customer_otps')->where('mobile', $mobile)->latest('id')->first();
        if (! $otp || $otp->used_at || now()->greaterThan($otp->expires_at) || $otp->attempts >= config('modules.customer.otp.max_attempts')) {
            return self::EXPIRED;
        }

        if (! hash_equals($otp->code_hash, $this->hash($mobile, $code))) {
            DB::table('customer_otps')->where('id', $otp->id)->increment('attempts');

            return self::WRONG;
        }

        DB::table('customer_otps')->where('id', $otp->id)->update(['used_at' => now()]);
        RateLimiter::clear('otp:mobile:'.$mobile);

        return self::OK;
    }

    /** Old codes are useless after their TTL; keep a day for support questions. */
    public function prune(): int
    {
        return DB::table('customer_otps')->where('created_at', '<', now()->subDay())->delete();
    }

    private function generate(int $length): string
    {
        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    private function hash(string $mobile, string $code): string
    {
        return hash_hmac('sha256', $mobile.'|'.$code, (string) config('app.key'));
    }
}
