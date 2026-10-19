<?php

/**
 * Khaneh Romantic — Footer newsletter box (inline, no page reload)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\Content\Support\Newsletter;

class NewsletterForm extends Component
{
    public string $contact = '';

    public ?string $done = null;

    public function subscribe(): void
    {
        $key = 'newsletter:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError('contact', 'کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 60);

        $result = Newsletter::subscribe($this->contact, 'footer');
        if ($result['status'] === 'invalid') {
            $this->addError('contact', 'یک ایمیل معتبر یا شماره موبایل ۱۱ رقمی وارد کنید.');

            return;
        }

        $this->done = $result['status'] === 'exists'
            ? 'این '.($result['subscriber']->channel === 'sms' ? 'شماره' : 'ایمیل').' از قبل عضو است؛ ممنون که همراه ما هستید!'
            : 'عضویت شما ثبت شد؛ خبرهای خوب در راه است!';
        $this->reset('contact');
    }

    public function render()
    {
        return view('content::livewire.newsletter-form');
    }
}
