<?php

/**
 * Khaneh Romantic — Account: name, email, newsletter
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Profile extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public bool $newsletter = false;

    public bool $saved = false;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();
        $this->first_name = (string) $customer->first_name;
        $this->last_name = (string) $customer->last_name;
        $this->email = (string) $customer->email;
        $this->newsletter = (bool) $customer->newsletter;
    }

    public function save(): void
    {
        $data = $this->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:120'],
            'newsletter' => ['boolean'],
        ], attributes: ['first_name' => 'نام', 'last_name' => 'نام خانوادگی', 'email' => 'ایمیل']);

        Auth::guard('customer')->user()->update($data + ['email' => $data['email'] ?: null]);
        $this->saved = true;
        $this->dispatch('toast', message: 'اطلاعات شما ذخیره شد');
    }

    public function render()
    {
        return view('customer::livewire.profile');
    }
}
