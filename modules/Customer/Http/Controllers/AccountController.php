<?php

/**
 * Khaneh Romantic — Customer account pages
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController
{
    public function login(Request $request)
    {
        // ?back=/p/…#reviews — return there after logging in (same-site paths only)
        $back = (string) $request->query('back');
        if (str_starts_with($back, '/') && ! str_starts_with($back, '//') && ! str_contains($back, '\\')) {
            $request->session()->put('url.intended', url($back));
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->intended(route('customer.account'));
        }

        return view('customer::login');
    }

    public function show(Request $request)
    {
        return view('customer::account.dashboard', [
            'customer' => Auth::guard('customer')->user(),
            'welcome' => $request->boolean('welcome'),
        ]);
    }

    public function addresses()
    {
        return view('customer::account.addresses');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
