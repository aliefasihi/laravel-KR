<?php

/**
 * Shared fixture for checkout/order tests: catalog + a logged-in customer with an address,
 * payment methods switched on, SMS captured.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Auth;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Checkout\Support\Cart;
use Modules\Customer\Models\Address;
use Modules\Customer\Models\Customer;
use Modules\Search\Tests\Feature\SearchTestCase;
use Modules\Sms\Support\Sms;
use Modules\Sms\Support\SmsDriver;

abstract class ShopTestCase extends SearchTestCase
{
    protected Customer $customer;

    protected Address $tehran;

    protected Address $shiraz;

    /** @var list<array{0: string, 1: string, 2: array}> */
    public array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog();

        $this->customer = Customer::create(['mobile' => '09121234567', 'first_name' => 'علی', 'last_name' => 'فصیحی']);
        $this->tehran = $this->customer->addresses()->create([
            'recipient_name' => 'علی فصیحی', 'recipient_mobile' => '09121234567', 'province' => 'تهران', 'city' => 'تهران', 'address' => 'خیابان ولیعصر', 'postal_code' => '1234567890',
        ]);
        $this->shiraz = $this->customer->addresses()->create([
            'recipient_name' => 'علی فصیحی', 'recipient_mobile' => '09121234567', 'province' => 'فارس', 'city' => 'شیراز', 'address' => 'خیابان زند',
        ]);
        Auth::guard('customer')->login($this->customer);

        Settings::set([
            'payment.zarinpal_enabled' => true, 'payment.zarinpal_merchant' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            'payment.card_enabled' => true, 'payment.card_number' => '6037991234567890', 'payment.card_holder' => 'خانه رمانتیک',
            'payment.cod_enabled' => true, 'payment.cod_provinces' => ['تهران'], 'payment.cod_max' => 0,
        ]);

        $sms = &$this->sms;
        app(Sms::class)->use(new class($sms) implements SmsDriver
        {
            public function __construct(private array &$sms) {}

            public function sendOtp(string $mobile, string $code): void {}

            public function sendTemplate(string $mobile, string $template, array $values): void
            {
                $this->sms[] = [$mobile, $template, $values];
            }

            public function sendText(string $mobile, string $text): void {}
        });
    }

    protected function variant(string $sku): ProductVariant
    {
        return Product::query()->where('sku', $sku)->firstOrFail()->variants()->firstOrFail();
    }

    /** Pan GK-7710 (84,500,000 sale) ×$qty, candy dish 3217073 (29,500,000) ×1 */
    protected function fillCart(int $qty = 1): Cart
    {
        $cart = app(Cart::class);
        $cart->add($this->variant('GK-7710')->id, $qty);
        $cart->add($this->variant('3217073')->id, 1);

        return $cart;
    }
}
