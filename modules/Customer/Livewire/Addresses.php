<?php

/**
 * Khaneh Romantic — Account: delivery addresses
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Livewire;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\Customer\Models\Address;
use Modules\Customer\Support\Mobile;

class Addresses extends Component
{
    public ?int $editing = null;   // address id, 0 = new, null = closed

    public array $form = [];

    public function create(): void
    {
        $customer = Auth::guard('customer')->user();
        $this->editing = 0;
        $this->form = [
            'title' => '', 'recipient_name' => $customer->fullName(), 'recipient_mobile' => $customer->mobile,
            'province' => 'تهران', 'city' => '', 'address' => '', 'plaque' => '', 'unit' => '', 'postal_code' => '', 'is_default' => false,
        ];
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
        $this->form['recipient_mobile'] = Mobile::normalize($this->form['recipient_mobile'] ?? '') ?? ($this->form['recipient_mobile'] ?? '');
        $this->form['postal_code'] = PersianText::toLatinDigits((string) ($this->form['postal_code'] ?? ''));

        $data = $this->validate([
            'form.title' => ['nullable', 'string', 'max:40'],
            'form.recipient_name' => ['required', 'string', 'max:80'],
            'form.recipient_mobile' => ['required', 'regex:/^09\d{9}$/'],
            'form.province' => ['required', 'in:'.implode(',', config('modules.customer.provinces'))],
            'form.city' => ['required', 'string', 'max:60'],
            'form.address' => ['required', 'string', 'max:500'],
            'form.plaque' => ['nullable', 'string', 'max:20'],
            'form.unit' => ['nullable', 'string', 'max:20'],
            'form.postal_code' => ['nullable', 'digits:10'],
            'form.is_default' => ['boolean'],
        ], [
            'form.recipient_mobile.regex' => 'شماره‌ی موبایل گیرنده درست نیست.',
            'form.postal_code.digits' => 'کد پستی ۱۰ رقم است.',
        ], [
            'form.recipient_name' => 'نام گیرنده', 'form.recipient_mobile' => 'موبایل گیرنده', 'form.province' => 'استان',
            'form.city' => 'شهر', 'form.address' => 'نشانی',
        ])['form'];

        $customer = Auth::guard('customer')->user();
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
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
            'provinces' => config('modules.customer.provinces'),
        ]);
    }
}
