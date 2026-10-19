<?php

/**
 * Khaneh Romantic — Newsletter sign-up without JavaScript, and the unsubscribe link
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Content\Models\NewsletterSubscriber;
use Modules\Content\Support\Newsletter;

class NewsletterController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $result = Newsletter::subscribe((string) $request->input('contact'), 'footer');

        if ($result['status'] === 'invalid') {
            return back()
                ->withErrors(['contact' => 'یک ایمیل معتبر یا شماره موبایل ۱۱ رقمی وارد کنید.'], 'newsletter')
                ->withInput()
                ->withFragment('newsletter-title');
        }

        return back()->with('status', $result['status'] === 'exists' ? 'از قبل عضو خبرنامه هستید؛ ممنون!' : 'عضویت شما ثبت شد؛ خبرهای خوب در راه است!');
    }

    public function unsubscribe(NewsletterSubscriber $subscriber): View
    {
        Newsletter::unsubscribe($subscriber);

        return view('content::pages.unsubscribed', ['subscriber' => $subscriber]);
    }
}
