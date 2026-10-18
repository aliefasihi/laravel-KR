<?php

/**
 * Khaneh Romantic — Pages for logged-in customers (redirects to the OTP login and back)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateCustomer
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('customer')->check()) {
            return redirect()->guest(route('customer.login'));
        }

        return $next($request);
    }
}
