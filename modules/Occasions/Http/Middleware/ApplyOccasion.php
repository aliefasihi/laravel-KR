<?php

/**
 * Khaneh Romantic — Picks this request's occasion and hands it to the views as config('khaneh.occasion')
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Shared views (promo bar, search box, header, home, product card) only read that config key, so
 * they don't depend on this module: switched off, the key is simply empty.
 */

namespace Modules\Occasions\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Occasions\Support\Occasions;
use Symfony\Component\HttpFoundation\Response;

class ApplyOccasion
{
    public function handle(Request $request, Closure $next): Response
    {
        Occasions::reset();
        if (! $request->is('admin', 'admin/*', 'livewire*')) {
            config(['khaneh.occasion' => Occasions::current($request)]);
        }

        return $next($request);
    }
}
