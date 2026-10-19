<?php

/**
 * Khaneh Romantic — Remembers ?utm_*=… / gclid (first touch of the session) for the forms inbox
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Crm\Support\Campaign;
use Symfony\Component\HttpFoundation\Response;

class CaptureCampaign
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession() && ! $request->session()->has(Campaign::SESSION_KEY)) {
            $campaign = collect(Campaign::KEYS)
                ->mapWithKeys(fn ($key) => [$key => Str::limit((string) $request->query($key, ''), 120, '')])
                ->filter()->all();
            if ($campaign) {
                $request->session()->put(Campaign::SESSION_KEY, $campaign + ['landing' => Str::limit($request->path(), 200, '')]);
            }
        }

        return $next($request);
    }
}
