<?php

/**
 * Khaneh Romantic — Account: delivery addresses
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\Customer\Models\Address;
use Modules\Customer\Support\AddressForm;

class Addresses extends Component
{
    public ?int $editing = null;   // address id, 0 = new, null = closed

    public array $form = [];

    public function create(): void
    {
        $customer = Auth::guard('customer')->user();
        $this->editing = 0;
        $this->form = AddressForm::blank($customer, account: true);
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        $address = $this->find($id);
        $this->editing = $address->id;
        $this->form = $address->only(['title', 'recipient_name', 'recipient_mobile', 'province', 'city', 'address', 'plaque', 'unit', 'postal_code', 'is_default']);
        $this->form = array_map(fn ($v) => is_bool($v) ? $v : (string) $v, $this->form);
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $data = AddressForm::validate($this, 'form', account: true);

        $customer = Auth::guard('customer')->user();
        $this->editing
            ? $this->find($this->editing)->update($data)
            : $customer->addresses()->create($data);

        $this->editing = null;
        $this->dispatch('toast', message: 'نشانی ذخیره شد');
    }

    public function makeDefault(int $id): void
    {
        $this->find($id)->update(['is_default' => true]);
    }

    public function delete(int $id): void
    {
        $this->find($id)->delete();
    }

    public function cancel(): void
    {
        $this->editing = null;
    }

    private function find(int $id): Address
    {
        return Auth::guard('customer')->user()->addresses()->whereKey($id)->firstOrFail();
    }

    public function render()
    {
        return view('customer::livewire.addresses', [
            'addresses' => Auth::guard('customer')->user()->addresses()->get(),
        ]);
    }
}
