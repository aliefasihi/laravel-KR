<?php

/**
 * OTP login, limits, account pages and addresses.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Tests\Feature;

use App\Core\Support\PersianText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\Customer\Livewire\Addresses;
use Modules\Customer\Livewire\Login;
use Modules\Customer\Livewire\Profile;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\Customer\Support\Otp;
use Modules\Sms\Support\Sms;
use Modules\Sms\Support\SmsDriver;
use Modules\Sms\Support\SmsException;
use Tests\TestCase;

class CustomerLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{0: string, 1: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('otp:mobile:09121234567');
        RateLimiter::clear('otp:ip:127.0.0.1');

        $sent = &$this->sent;
        app(Sms::class)->use(new class($sent) implements SmsDriver
        {
            public function __construct(private array &$sent) {}

            public function sendOtp(string $mobile, string $code): void
            {
                $this->sent[] = [$mobile, $code];
            }

            public function sendTemplate(string $mobile, string $template, array $values): void {}

            public function sendText(string $mobile, string $text): void {}
        });
    }

    private function lastCode(): string
    {
        return end($this->sent)[1];
    }

    public function test_mobile_numbers_are_normalized(): void
    {
        $this->assertSame('09121234567', Mobile::normalize('۰۹۱۲ ۱۲۳ ۴۵۶۷'));
        $this->assertSame('09121234567', Mobile::normalize('+98 912-123-4567'));
        $this->assertSame('09121234567', Mobile::normalize('00989121234567'));
        $this->assertSame('09121234567', Mobile::normalize('9121234567'));
        $this->assertNull(Mobile::normalize('0212345678'));
        $this->assertNull(Mobile::normalize('0912123'));
    }

    public function test_codes_expire_burn_and_are_rate_limited(): void
    {
        $otp = app(Otp::class);

        $this->assertSame(Otp::SENT, $otp->send('09121234567', '127.0.0.1')['status']);
        $code = $this->lastCode();
        $this->assertMatchesRegularExpression('/^\d{5}$/', $code);
        $this->assertDatabaseMissing('customer_otps', ['code_hash' => $code]); // never stored in plain text

        // «ارسال دوباره» only after 60 s
        $again = $otp->send('09121234567', '127.0.0.1');
        $this->assertSame(Otp::WAIT, $again['status']);
        $this->assertGreaterThan(50, $again['retry_in']);

        // five wrong tries burn the code, even the right one fails afterwards
        $wrong = $code === '00000' ? '11111' : '00000';
        foreach (range(1, 5) as $i) {
            $this->assertSame(Otp::WRONG, $otp->verify('09121234567', $wrong));
        }
        $this->assertSame(Otp::EXPIRED, $otp->verify('09121234567', $code));

        // a new code works once, then it's used up
        $this->travel(61)->seconds();
        $otp->send('09121234567', '127.0.0.1');
        $this->assertSame(Otp::OK, $otp->verify('09121234567', $this->lastCode()));
        $this->assertSame(Otp::EXPIRED, $otp->verify('09121234567', $this->lastCode()));

        // codes live 2 minutes
        $this->travel(61)->seconds();
        $otp->send('09121234567', '127.0.0.1');
        $this->travel(121)->seconds();
        $this->assertSame(Otp::EXPIRED, $otp->verify('09121234567', $this->lastCode()));

        // at most 5 codes an hour per number
        RateLimiter::clear('otp:mobile:09121234567');
        foreach (range(1, 5) as $i) {
            $this->travel(61)->seconds();
            $this->assertSame(Otp::SENT, $otp->send('09121234567', null)['status']);
        }
        $this->travel(61)->seconds();
        $this->assertSame(Otp::LIMITED, $otp->send('09121234567', null)['status']);
    }

    public function test_login_creates_the_customer_and_returns_to_the_intended_page(): void
    {
        $this->get('/account')->assertRedirect(route('customer.login'));

        $component = Livewire::test(Login::class)
            ->set('mobile', '0912')->call('send')->assertHasErrors('mobile')
            ->set('mobile', '۰۹۱۲۱۲۳۴۵۶۷')->call('send')
            ->assertHasNoErrors()->assertSet('step', 'code')->assertSet('verifiedMobile', '09121234567')
            ->assertDispatched('otp-sent');

        $component->set('code', '123')->call('verify')->assertHasErrors('code');
        $code = $this->lastCode();
        $component->set('code', PersianText::toPersianDigits($code))->call('verify')
            ->assertHasNoErrors()
            ->assertRedirect(route('customer.account'));

        $customer = Customer::query()->where('mobile', '09121234567')->firstOrFail();
        $this->assertNotNull($customer->mobile_verified_at);
        $this->assertTrue(Auth::guard('customer')->check());
        $this->assertFalse(Auth::guard('web')->check()); // never an admin session

        $this->get('/account')->assertOk()->assertSee('به خانه رمانتیک خوش آمدید');
        $this->get('/admin')->assertRedirect(); // the admin panel still asks for an admin login
    }

    public function test_sms_failure_is_reported(): void
    {
        app(Sms::class)->use(new class implements SmsDriver
        {
            public function sendOtp(string $mobile, string $code): void
            {
                throw new SmsException('down');
            }

            public function sendTemplate(string $mobile, string $template, array $values): void {}

            public function sendText(string $mobile, string $text): void {}
        });

        Livewire::test(Login::class)->set('mobile', '09121234567')->call('send')
            ->assertHasErrors('mobile')->assertSet('step', 'mobile');
        $this->assertSame(0, DB::table('customer_otps')->count());
    }

    public function test_profile_and_addresses(): void
    {
        $customer = Customer::create(['mobile' => '09121234567']);
        Auth::guard('customer')->login($customer);

        Livewire::test(Profile::class)
            ->set('first_name', '')->call('save')->assertHasErrors('first_name')
            ->set('first_name', 'علی')->set('last_name', 'فصیحی')->set('email', 'ali@example.com')->call('save')
            ->assertHasNoErrors()->assertDispatched('toast');
        $this->assertSame('علی فصیحی', $customer->fresh()->fullName());

        $addresses = Livewire::test(Addresses::class)->call('create')
            ->assertSet('form.recipient_name', 'علی فصیحی')
            ->set('form.city', 'تهران')->set('form.address', 'خیابان ولیعصر')->set('form.postal_code', '۱۲۳۴۵')
            ->call('save')->assertHasErrors('form.postal_code')
            ->set('form.postal_code', '۱۲۳۴۵۶۷۸۹۰')->call('save')->assertHasNoErrors();

        $first = $customer->addresses()->firstOrFail();
        $this->assertTrue($first->is_default);  // the first address is the default
        $this->assertSame('1234567890', $first->postal_code);

        $addresses->call('create')->set('form.title', 'محل کار')->set('form.city', 'تهران')->set('form.address', 'میدان ونک')
            ->set('form.is_default', true)->call('save');
        $this->assertSame(['محل کار'], $customer->addresses()->where('is_default', true)->pluck('title')->all());

        // can't touch someone else's address
        $other = Customer::create(['mobile' => '09350000000'])->addresses()->create([
            'recipient_name' => 'x', 'recipient_mobile' => '09350000000', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'x',
        ]);
        $addresses->call('delete', $other->id)->assertStatus(404);
        $this->assertModelExists($other);

        $this->get('/account/addresses')->assertOk()->assertSee('میدان ونک');
        $this->post('/account/logout')->assertRedirect(route('home'));
        $this->assertFalse(Auth::guard('customer')->check());
    }
}
