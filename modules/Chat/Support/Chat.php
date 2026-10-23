<?php

/**
 * Khaneh Romantic — Is the site chat on (here)?
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;

final class Chat
{
    public static function enabled(): bool
    {
        return Modules::enabled('Chat') && (bool) Settings::get('chat.enabled', false);
    }

    /** not on checkout / payment (nothing should distract from paying) */
    public static function onPage(?string $route): bool
    {
        if (! self::enabled()) {
            return false;
        }
        foreach (['checkout.', 'order.checkout', 'payment.', 'filament.'] as $prefix) {
            if ($route && str_starts_with($route, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
