<?php

/**
 * Khaneh Romantic — The delivery-address form, shared by «نشانی‌ها» in the account and the
 * checkout's «نشانی تازه»: blank values, digit clean-up, validation rules and messages.
 * The fields are in customer::partials.address-fields.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Support;

use App\Core\Support\PersianText;
use Livewire\Component;
use Modules\Customer\Models\Customer;

final class AddressForm
{
    /** Empty form for this customer (their name and number prefilled). */
    public static function blank(Customer $customer, bool $account = false): array
    {
        return [
            ...($account ? ['title' => ''] : []),
            'recipient_name' => $customer->fullName(), 'recipient_mobile' => $customer->mobile,
            'province' => 'تهران', 'city' => '', 'address' => '', 'plaque' => '', 'unit' => '', 'postal_code' => '',
            ...($account ? ['is_default' => false] : []),
        ];
    }

    /**
     * Validates $component->{$property} (digits in any script are accepted) and returns the
     * values ready to save (empty strings → null).
     */
    public static function validate(Component $component, string $property, bool $account = false): array
    {
        $form = $component->{$property};
        $form['recipient_mobile'] = Mobile::normalize($form['recipient_mobile'] ?? '') ?? ($form['recipient_mobile'] ?? '');
        $form['postal_code'] = PersianText::toLatinDigits((string) ($form['postal_code'] ?? ''));
        $component->{$property} = $form;

        $p = $property.'.';
        $rules = [
            $p.'recipient_name' => ['required', 'string', 'max:80'],
            $p.'recipient_mobile' => ['required', 'regex:/^09\d{9}$/'],
            $p.'province' => ['required', 'in:'.implode(',', config('modules.customer.provinces'))],
            $p.'city' => ['required', 'string', 'max:60'],
            $p.'address' => ['required', 'string', 'max:500'],
            $p.'plaque' => ['nullable', 'string', 'max:20'],
            $p.'unit' => ['nullable', 'string', 'max:20'],
            $p.'postal_code' => ['nullable', 'digits:10'],
        ];
        if ($account) {
            $rules += [$p.'title' => ['nullable', 'string', 'max:40'], $p.'is_default' => ['boolean']];
        }

        $data = $component->validate($rules, [
            $p.'recipient_mobile.regex' => 'شماره‌ی موبایل گیرنده درست نیست.',
            $p.'postal_code.digits' => 'کد پستی ۱۰ رقم است.',
        ], [
            $p.'recipient_name' => 'نام گیرنده', $p.'recipient_mobile' => 'موبایل گیرنده', $p.'province' => 'استان',
            $p.'city' => 'شهر', $p.'address' => 'نشانی',
        ])[$property];

        return array_map(fn ($value) => $value === '' ? null : $value, $data);
    }
}
