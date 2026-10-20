<?php

/**
 * Khaneh Romantic — Checkout: address → shipping → payment → place order
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * One page, every choice re-prices the cart through the same pipeline the order uses.
 */

namespace Modules\Order\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Checkout\Support\Cart;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Customer\Models\Address;
use Modules\Customer\Support\AddressForm;
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
        $data = AddressForm::validate($this, 'address');

        $created = $this->customer()->addresses()->create($data);
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

    private function quote(Cart $cart, ?Address $address = null): Quote
    {
        $address ??= $this->selectedAddress();

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
        $this->address = AddressForm::blank($this->customer());
    }

    public function render(Cart $cart)
    {
        $addresses = $this->customer()->addresses()->get();
        $address = $this->addressId ? $addresses->firstWhere('id', $this->addressId) : null;
        $quote = $this->quote($cart, $address);
        $shippingOptions = collect($quote->meta['shipping_options'] ?? []);
        $payments = PaymentMethods::availableFor($quote);

        // keep the choices valid as the address / cart change
        if ($shippingOptions->isNotEmpty() && ! $shippingOptions->contains('code', $this->shippingMethod)) {
            $this->shippingMethod = $shippingOptions->first()['code'];
            $quote = $this->quote($cart, $address);
        }
        if (! array_key_exists((string) $this->paymentMethod, $payments)) {
            $this->paymentMethod = array_key_first($payments);
        }

        $this->shownTotal = $quote->total();

        return view('order::livewire.checkout', [
            'quote' => $quote,
            'lines' => $quote->context->lines,
            'addresses' => $addresses,
            'shippingOptions' => $shippingOptions,
            'payments' => $payments,
        ]);
    }
}
