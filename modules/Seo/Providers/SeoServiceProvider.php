<?php

/**
 * Khaneh Romantic — SEO module (legacy redirects, meta, sitemaps)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Events\CategoryUrlChanged;
use Modules\Catalog\Events\ProductUrlChanged;
use Modules\Seo\Http\Middleware\LegacyQueryStringRedirect;
use Modules\Seo\Listeners\CreateRedirectForChangedUrl;
use Modules\Seo\Support\LegacyProductMatcher;
use Modules\Seo\Support\RedirectResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SeoServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Event::listen([ProductUrlChanged::class, CategoryUrlChanged::class], CreateRedirectForChangedUrl::class);

        $this->app['router']->pushMiddlewareToGroup('web', LegacyQueryStringRedirect::class);

        $this->registerLegacyRedirects();
    }

    /**
     * Every GET that would end in 404 — an unknown path, or a known route whose
     * model is missing (e.g. an old /p/ slug) — first checks the redirects table,
     * then looks for the product an old WooCommerce URL meant (suggestion + "did you mean").
     */
    private function registerLegacyRedirects(): void
    {
        $this->app->make(ExceptionHandler::class)->renderable(
            function (NotFoundHttpException $e, Request $request) {
                if (! $request->isMethod('GET') || $request->is('api/*')) {
                    return null;
                }

                return $this->app->make(RedirectResolver::class)->respond($request->getPathInfo())
                    ?? rescue(fn () => $this->app->make(LegacyProductMatcher::class)->respond($request), report: true);
            }
        );
    }
}
