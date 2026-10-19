<?php

/**
 * Khaneh Romantic — «تماس با ما» form → forms inbox (+ SMS to the staff if switched on)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Livewire;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Crm\Support\Forms;
use Modules\Customer\Support\Mobile;

class ContactForm extends Component
{
    public const DEFAULT_SUBJECTS = ['مشاوره‌ی خرید', 'پیگیری سفارش', 'خرید عمده و همکاری', 'پیشنهاد و انتقاد', 'سایر'];

    /** @var list<string> */
    #[Locked]
    public array $subjects = [];

    public string $name = '';

    public string $mobile = '';

    public string $email = '';

    public string $subject = '';

    public string $orderNumber = '';

    public string $message = '';

    public string $website = '';   // honeypot

    #[Locked]
    public int $startedAt = 0;

    public ?int $sentId = null;

    public function mount(array $subjects = []): void
    {
        $this->subjects = array_values(array_filter($subjects)) ?: self::DEFAULT_SUBJECTS;
        $this->subject = $this->subjects[0];
        $this->startedAt = now()->getTimestamp();
        if ($customer = Auth::guard('customer')->user()) {
            $this->name = $customer->fullName();
            $this->mobile = $customer->mobile;
            $this->email = (string) $customer->email;
        }
    }

    public function send(): void
    {
        $this->mobile = PersianText::toLatinDigits($this->mobile);
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'mobile' => ['required', fn ($a, $v, $fail) => Mobile::normalize($v) ? null : $fail('شماره‌ی موبایل درست نیست.')],
            'email' => ['nullable', 'email', 'max:120'],
            'subject' => ['required', 'in:'.implode(',', $this->subjects)],
            'orderNumber' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [], ['name' => 'نام', 'mobile' => 'موبایل', 'email' => 'ایمیل', 'subject' => 'موضوع', 'message' => 'پیام']);

        // bots: filled the hidden field or sent faster than a person can type
        if ($this->website !== '' || now()->getTimestamp() - $this->startedAt < 3) {
            $this->sentId = 0;

            return;
        }
        $key = 'contact-form:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('message', 'پیام‌های زیادی فرستاده‌اید؛ کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $customer = Auth::guard('customer')->user();
        $submission = Forms::record('contact', array_filter([
            'name' => trim($this->name),
            'mobile' => Mobile::normalize($this->mobile),
            'email' => trim($this->email) ?: null,
            'subject' => $this->subject,
            'order_number' => PersianText::toLatinDigits(trim($this->orderNumber)) ?: null,
            'message' => trim($this->message),
        ], fn ($v) => $v !== null), [
            'name' => $this->name, 'mobile' => $this->mobile, 'email' => $this->email, 'customer_id' => $customer?->id,
        ]);

        $this->sentId = $submission?->id ?? 0;
        $this->reset(['message', 'orderNumber']);
    }

    public function render()
    {
        return view('content::livewire.contact-form');
    }
}
