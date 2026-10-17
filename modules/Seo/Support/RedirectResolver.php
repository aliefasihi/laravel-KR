<?php

/**
 * Khaneh Romantic — Looks up a legacy key in the redirects table
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Support;

use App\Core\Support\UrlPath;
use Illuminate\Http\Response as HttpResponse;
use Modules\Seo\Models\Redirect;
use Symfony\Component\HttpFoundation\Response;

class RedirectResolver
{
    /** Returns the redirect (or 410) response for $key, or null when unknown. */
    public function respond(string $key): ?Response
    {
        $redirect = Redirect::query()->where('from_hash', UrlPath::hash($key))->first();

        if ($redirect === null) {
            return null;
        }

        $redirect->increment('hits', 1, ['last_hit_at' => now()]);

        if ($redirect->status_code === 410) {
            return new HttpResponse('Gone', 410);
        }

        // away(): keep the target byte-for-byte (url()->to() would strip a category's trailing slash).
        return redirect()->away(rtrim(url('/'), '/').$redirect->to_path, $redirect->status_code);
    }
}
