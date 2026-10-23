<?php

/**
 * Khaneh Romantic — «درخواست همکاری» (wholesale application) for a logged-in customer
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Livewire;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Wholesale\Models\WholesaleAccount;
use Modules\Wholesale\Support\Wholesale;

class ApplicationForm extends Component
{
    use WithFileUploads;

    public string $shop_name = '';

    public string $owner_name = '';

    public string $business_type = '';

    public string $province = '';

    public string $city = '';

    public string $phone = '';

    public string $website = '';

    public string $description = '';

    /** @var mixed licence or shop photo */
    public $document = null;

    public function mount(Wholesale $wholesale): void
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer, 403);

        $account = $wholesale->account($customer->id);
        if ($account) {   // a rejected application, sent again: start from what they wrote
            foreach (['shop_name', 'owner_name', 'business_type', 'province', 'city', 'phone', 'website', 'description'] as $field) {
                $this->{$field} = (string) ($account->{$field} ?? '');
            }
        } else {
            $this->owner_name = $customer->fullName();
        }
    }

    public function submit(Wholesale $wholesale)
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer, 403);

        $this->phone = PersianText::toLatinDigits($this->phone);
        $this->validate([
            'shop_name' => ['required', 'string', 'max:80'],
            'owner_name' => ['required', 'string', 'max:60'],
            'business_type' => ['required', 'in:'.implode(',', array_keys(WholesaleAccount::TYPES))],
            'province' => ['required', 'in:'.implode(',', config('modules.customer.provinces'))],
            'city' => ['required', 'string', 'max:40'],
            'phone' => ['nullable', 'regex:/^0\d{9,10}$/'],
            'website' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:6144'],
        ], ['phone.regex' => 'شماره‌ی ثابت را با کد شهر بنویسید؛ مثل ۰۲۱۸۸۸۸۸۸۸۸.'], [
            'shop_name' => 'نام فروشگاه یا شرکت', 'owner_name' => 'نام و نام خانوادگی', 'business_type' => 'نوع کسب‌وکار',
            'province' => 'استان', 'city' => 'شهر', 'phone' => 'تلفن ثابت', 'website' => 'پیج یا سایت', 'document' => 'مدرک',
        ]);

        $key = 'wholesale-apply:'.$customer->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('shop_name', 'کمی بعد دوباره امتحان کنید.');

            return null;
        }
        RateLimiter::hit($key, 3600);

        $account = $wholesale->apply($customer, $this->only(['shop_name', 'owner_name', 'business_type', 'province', 'city', 'phone', 'website', 'description']), $this->document);

        session()->flash('status', $account->isApproved()
            ? 'همکاری شما فعال شد. از همین حالا قیمت‌ها برای شما عمده است.'
            : 'درخواست همکاری ثبت شد. بعد از بررسی، نتیجه را پیامک می‌کنیم.');

        return $this->redirectRoute('wholesale', navigate: false);
    }

    public function render()
    {
        return view('wholesale::livewire.apply', [
            'types' => WholesaleAccount::TYPES,
            'provinces' => config('modules.customer.provinces'),
        ]);
    }
}
