<?php

/**
 * SMS providers: request shapes (HTTP faked) and errors.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Sms\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Sms\Support\Sms;
use Modules\Sms\Support\SmsException;
use Tests\TestCase;

class SmsDriversTest extends TestCase
{
    use RefreshDatabase;

    public function test_kavenegar_lookup(): void
    {
        Settings::set(['sms.driver' => 'kavenegar', 'sms.kavenegar_key' => 'K3Y', 'sms.kavenegar_otp_template' => 'login']);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 200, 'message' => 'تایید شد']])]);

        app(Sms::class)->otp('09121234567', '48213');
        app(Sms::class)->template('09121234567', 'order-sent', ['KR-1042', 'پست پیشتاز', 'تا ۳ روز کاری']);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.kavenegar.com/v1/K3Y/verify/lookup.json'
            && $r['receptor'] === '09121234567' && $r['token'] === '48213' && $r['template'] === 'login');
        Http::assertSent(fn (Request $r) => $r['template'] === 'order-sent' && $r['token2'] === 'پست‌پیشتاز' && $r['token3'] === 'تا‌۳‌روز‌کاری');
    }

    public function test_smsir_verify_and_errors(): void
    {
        Settings::set(['sms.driver' => 'smsir', 'sms.smsir_key' => 'S3CRET', 'sms.smsir_otp_template' => 456789]);
        Http::fake(['api.sms.ir/*' => Http::sequence()
            ->push(['status' => 1, 'message' => 'موفق'])
            ->push(['status' => 0, 'message' => 'اعتبار کافی نیست'], 200)]);

        app(Sms::class)->otp('09121234567', '48213');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.sms.ir/v1/send/verify'
            && $r->hasHeader('X-API-KEY', 'S3CRET')
            && $r['templateId'] === 456789
            && $r['parameters'] === [['name' => 'CODE', 'value' => '48213']]);

        $this->expectException(SmsException::class);
        $this->expectExceptionMessage('اعتبار کافی نیست');
        app(Sms::class)->otp('09121234567', '11111');
    }
}
