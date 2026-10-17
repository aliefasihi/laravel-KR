<?php

/**
 * Khaneh Romantic — WordPress query-string URLs on the homepage
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * WordPress answers /?p=123, /?page_id=45, /?product=slug, /?product_cat=slug.
 * The importer stores them as keys like "?p=123"; this resolves them in one hop.
 */

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Seo\Support\RedirectResolver;
use Symfony\Component\HttpFoundation\Response;

class LegacyQueryStringRedirect
{
    private const PARAMS = ['p', 'page_id', 'product', 'product_cat'];

    public function __construct(private RedirectResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getPathInfo() === '/' && $request->isMethod('GET')) {
            foreach (self::PARAMS as $param) {
                if ($request->filled($param)) {
                    $response = $this->resolver->respond('?'.$param.'='.$request->query($param));

                    if ($response !== null) {
                        return $response;
                    }
                }
            }
        }

        return $next($request);
    }
}
