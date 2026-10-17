<?php

/**
 * Khaneh Romantic — Footer newsletter sign-up
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Http\Controllers;

use App\Core\Support\PersianText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Content\Models\NewsletterSubscriber;

class NewsletterController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $contact = trim(PersianText::toLatinDigits((string) $request->input('contact')));
        $mobile = preg_replace('/^(\+98|0098|98)/', '0', preg_replace('/[\s-]/', '', $contact));

        if (preg_match('/^09\d{9}$/', $mobile)) {
            $data = ['contact' => $mobile, 'channel' => 'sms'];
        } elseif (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $data = ['contact' => mb_strtolower($contact), 'channel' => 'email'];
        } else {
            return back()
                ->withErrors(['contact' => 'یک ایمیل معتبر یا شماره موبایل ۱۱ رقمی وارد کنید.'], 'newsletter')
                ->withInput()
                ->withFragment('newsletter-title');
        }

        NewsletterSubscriber::query()->firstOrCreate(['contact' => $data['contact']], $data);

        return back()->with('status', 'عضویت شما ثبت شد؛ خبرهای خوب در راه است!');
    }
}
