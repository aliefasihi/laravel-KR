<?php

/**
 * Khaneh Romantic — The campaign a visitor came from (utm_*, gclid), kept for the session
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Support;

final class Campaign
{
    public const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid'];

    public const SESSION_KEY = 'crm.campaign';

    /** @return array<string, string> */
    public static function current(): array
    {
        $request = request();

        return $request->hasSession() ? (array) $request->session()->get(self::SESSION_KEY, []) : [];
    }
}
