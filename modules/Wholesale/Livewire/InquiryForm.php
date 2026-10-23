<?php

/**
 * Khaneh Romantic — «استعلام خرید عمده / سازمانی»: one-off large orders (corporate gifts, events…)
 * without a wholesale account; lands in صندوق فرم‌ها.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Livewire;

use App\Core\Livewire\StopsBots;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\Crm\Support\Forms;
use Modules\Customer\Support\Mobile;

class InquiryForm extends Component
{
    use StopsBots;

    public string $name = '';

    public string $mobile = '';

    public string $company = '';

    public string $city = '';

    public string $items = '';

    public string $budget = '';

    public string $date = '';

    public bool $sent = false;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();
        if ($customer) {
            $this->name = $customer->fullName();
            $this->mobile = PersianText::toPersianDigits((string) $customer->mobile);
        }
    }

    public function submit(): void
    {
        $this->mobile = PersianText::toLatinDigits($this->mobile);
        $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'mobile' => ['required', fn ($a, $v, $fail) => Mobile::normalize($v) ? null : $fail('شماره‌ی موبایل درست نیست.')],
            'company' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:40'],
            'items' => ['required', 'string', 'min:5', 'max:2000'],
            'budget' => ['nullable', 'string', 'max:60'],
            'date' => ['nullable', 'string', 'max:60'],
        ], [], ['name' => 'نام', 'mobile' => 'موبایل', 'items' => 'کالاها و تعداد', 'company' => 'شرکت', 'city' => 'شهر']);

        if ($this->looksLikeBot()) {
            $this->sent = true;

            return;
        }
        $key = 'wholesale-inquiry:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 4)) {
            $this->addError('items', 'کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $mobile = Mobile::normalize($this->mobile);
        Forms::record('wholesale_inquiry', array_filter([
            'name' => trim($this->name), 'mobile' => $mobile, 'company' => trim($this->company), 'city' => trim($this->city),
            'items' => trim($this->items), 'budget' => trim($this->budget), 'date' => trim($this->date),
        ]), ['name' => trim($this->name), 'mobile' => $mobile, 'customer_id' => Auth::guard('customer')->id()]);

        $this->sent = true;
    }

    public function render()
    {
        return view('wholesale::livewire.inquiry');
    }
}
