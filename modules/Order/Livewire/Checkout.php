<?php

/**
 * Khaneh Romantic — Checkout: address → shipping → payment → place order
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * One page, every choice re-prices the cart through the same pipeline the order uses.
 */

namespace Modules\Order\Livewire;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Checkout\Support\Cart;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Customer\Models\Address;
use Modules\Customer\Support\Mobile;
use Modules\Order\Payments\PaymentMethods;
use Modules\Order\Support\OrderException;
use Modules\Order\Support\PlaceOrder;

class Checkout extends Component
{
    public ?int $addressId = null;

    public ?string $shippingMethod = null;

    public ?string $paymentMethod = null;

    public string $note = '';

    public bool $addingAddress = false;

    public array $address = [];

    /** The total the shopper is looking at; the order is refused if the real total differs. */
    public int $shownTotal = 0;

    public function mount(Cart $cart): void
    {
        if ($cart->count() === 0) {
            $this->redirectRoute('checkout.cart');

            return;
        }

        $customer = $this->customer();
        $this->addressId = $customer->defaultAddress()?->id;
        $this->addingAddress = $this->addressId === null;
        $this->resetAddressForm();
    }

    #[On('cart-changed')]
    public function refreshTotals(): void {}

    public function updatedAddressId(): void
    {
        $this->shippingMethod = null;
        $this->resetErrorBag();
    }

    public function newAddress(): void
    {
        $this->resetAddressForm();
        $this->addingAddress = true;
    }

    public function saveAddress(): void
    {
        $this->address['recipient_mobile'] = Mobile::normalize($this->address['recipient_mobile'] ?? '') ?? ($this->address['recipient_mobile'] ?? '');
        $this->address['postal_code'] = PersianText::toLatinDigits((string) ($this->address['postal_code'] ?? ''));

        $data = $this->validate([
            'address.recipient_name' => ['required', 'string', 'max:80'],
            'address.recipient_mobile' => ['required', 'regex:/^09\d{9}$/'],
            'address.province' => ['required', 'in:'.implode(',', config('modules.customer.provinces'))],
            'address.city' => ['required', 'string', 'max:60'],
            'address.address' => ['required', 'string', 'max:500'],
            'address.plaque' => ['nullable', 'string', 'max:20'],
            'address.unit' => ['nullable', 'string', 'max:20'],
            'address.postal_code' => ['nullable', 'digits:10'],
        ], [
            'address.recipient_mobile.regex' => 'شماره‌ی موبایل گیرنده درست نیست.',
            'address.postal_code.digits' => 'کد پستی ۱۰ رقم است.',
        ], [
            'address.recipient_name' => 'نام گیرنده', 'address.recipient_mobile' => 'موبایل گیرنده', 'address.province' => 'استان',
            'address.city' => 'شهر', 'address.address' => 'نشانی',
        ])['address'];

        $created = $this->customer()->addresses()->create(array_map(fn ($v) => $v === '' ? null : $v, $data));
        $this->addressId = $created->id;
        $this->addingAddress = false;
        $this->shippingMethod = null;
    }

    public function place(Cart $cart, PlaceOrder $placeOrder): void
    {
        $this->resetErrorBag();
        $address = $this->selectedAddress();
        if (! $address) {
            $this->addError('address', 'نشانی تحویل را انتخاب کنید.');

            return;
        }

        $quote = $this->quote($cart);
        try {
            $order = $placeOrder->handle($this->customer(), $address, [
                'shipping_method' => $quote->meta['shipping']['code'] ?? $this->shippingMethod,
                'payment_method' => (string) $this->paymentMethod,
                'note' => trim($this->note) ?: null,
                'expected_total' => $this->shownTotal ?: $quote->total(),
            ]);
        } catch (OrderException $e) {
            $this->addError($e->field, $e->getMessage());

            return;
        }

        $url = PaymentMethods::find($order->payment_method)?->start($order);
        $this->redirect($url ?? route('customer.orders.show', ['order' => $order, 'placed' => 1]));
    }

    private function quote(Cart $cart): Quote
    {
        $address = $this->selectedAddress();

        return $cart->quote([
            'province' => $address?->province,
            'city' => $address?->city,
            'shippingMethod' => $this->shippingMethod,
            'paymentMethod' => $this->paymentMethod,
        ]);
    }

    private function selectedAddress(): ?Address
    {
        return $this->addressId ? $this->customer()->addresses()->whereKey($this->addressId)->first() : null;
    }

    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    private function resetAddressForm(): void
    {
        $customer = $this->customer();
        $this->address = [
            'recipient_name' => $customer->fullName(), 'recipient_mobile' => $customer->mobile, 'province' => 'تهران',
            'city' => '', 'address' => '', 'plaque' => '', 'unit' => '', 'postal_code' => '',
        ];
    }

    public function render(Cart $cart)
    {
        $quote = $this->quote($cart);
        $shippingOptions = collect($quote->meta['shipping_options'] ?? []);
        $payments = PaymentMethods::availableFor($quote);

        // keep the choices valid as the address / cart change
        if ($shippingOptions->isNotEmpty() && ! $shippingOptions->contains('code', $this->shippingMethod)) {
            $this->shippingMethod = $shippingOptions->first()['code'];
            $quote = $this->quote($cart);
        }
        if (! array_key_exists((string) $this->paymentMethod, $payments)) {
            $this->paymentMethod = array_key_first($payments);
        }

        $this->shownTotal = $quote->total();

        return view('order::livewire.checkout', [
            'quote' => $quote,
            'lines' => $quote->context->lines,
            'addresses' => $this->customer()->addresses()->get(),
            'shippingOptions' => $shippingOptions,
            'payments' => $payments,
            'provinces' => config('modules.customer.provinces'),
        ]);
    }
}
