<?php

/**
 * Khaneh Romantic — Puts the chosen design's views in front for this request (App\Core\Support\Designs)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Core\Http\Middleware;

use App\Core\Support\Designs;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyDesign
{
    public function handle(Request $request, Closure $next): Response
    {
        Designs::boot($request);

        return $next($request);
    }
}
