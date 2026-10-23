<?php

/**
 * Khaneh Romantic — Web push: subscribe / unsubscribe / renew, and campaign clicks
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Push\Models\PushCampaign;
use Modules\Push\Support\Push;

class PushController
{
    public function subscribe(Request $request, Push $push): JsonResponse
    {
        abort_unless(Push::enabled(), 404);
        $subscription = $push->subscribe((array) $request->input('subscription'), Auth::guard('customer')->id(), $request->userAgent());

        return response()->json(['ok' => (bool) $subscription], $subscription ? 200 : 422);
    }

    public function unsubscribe(Request $request, Push $push): JsonResponse
    {
        $push->unsubscribe((string) $request->input('endpoint'));

        return response()->json(['ok' => true]);
    }

    public function renew(Request $request, Push $push): JsonResponse
    {
        $ok = $push->renew((string) $request->input('old_endpoint'), (array) $request->input('subscription'));

        return response()->json(['ok' => $ok], $ok ? 200 : 404);
    }

    /** counts the click, then the campaign's page (tagged for the sales report's channels) */
    public function click(int $campaign): RedirectResponse
    {
        $record = PushCampaign::query()->find($campaign);
        $record?->increment('clicks');

        $target = (string) ($record?->url ?: '/');
        $host = parse_url($target, PHP_URL_HOST);
        if (($host && $host !== request()->getHost()) || ! preg_match('~^(/|https?://)~', $target) || str_starts_with($target, '//')) {
            $target = '/';   // only this site
        }
        $target .= (str_contains($target, '?') ? '&' : '?').'utm_source=push&utm_campaign=push-'.$campaign;

        return redirect()->to($target);
    }
}
