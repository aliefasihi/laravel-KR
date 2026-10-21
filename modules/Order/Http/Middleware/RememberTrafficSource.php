<?php

/**
 * Khaneh Romantic — Notes the shopper's traffic source on page views (Order\Support\TrafficSource)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Order\Support\TrafficSource;
use Symfony\Component\HttpFoundation\Response;

class RememberTrafficSource
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->is('livewire/*', 'admin/*', 'admin')) {
            TrafficSource::remember($request);
        }

        return $next($request);
    }
}
