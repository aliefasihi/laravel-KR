<?php

/**
 * Khaneh Romantic — ?ref=CODE on any page → a 30-day cookie, read when the visitor signs up
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Modules\Loyalty\Models\ReferralCode;
use Modules\Loyalty\Support\Referrals;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->query('ref') && ! auth('customer')->check()
            && ($code = Referrals::normalize((string) $request->query('ref')))
            && ReferralCode::query()->where('code', $code)->exists()) {
            Cookie::queue(Cookie::make(Referrals::COOKIE, $code, Referrals::COOKIE_DAYS * 24 * 60, httpOnly: true));
        }

        return $next($request);
    }
}
