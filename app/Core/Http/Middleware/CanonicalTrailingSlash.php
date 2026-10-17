<?php

/**
 * Khaneh Romantic — Trailing-slash policy
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Preserved WordPress category URLs keep their trailing slash (exact parity).
 * Every other URL is slash-less. The wrong form gets a single 301.
 * Routes opt in through their action array:
 *   Route::get('x/{path}', ['uses' => [Controller::class, 'show'], 'trailing_slash' => true]);
 * (An action key, not a route default, so it never leaks into controller arguments.)
 */

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $route = $request->route();
        $pathInfo = $request->getPathInfo();

        // Fallback (legacy redirect) requests resolve in one hop on their own.
        if ($route === null || $route->isFallback || $pathInfo === '/') {
            return $next($request);
        }

        $wantsSlash = (bool) $route->getAction('trailing_slash');
        $hasSlash = str_ends_with($pathInfo, '/');

        if ($wantsSlash === $hasSlash) {
            return $next($request);
        }

        $target = $wantsSlash ? $pathInfo.'/' : rtrim($pathInfo, '/');
        $query = $request->getQueryString();

        return redirect()->to($request->getSchemeAndHttpHost().$request->getBaseUrl().$target.($query ? '?'.$query : ''), 301);
    }
}
