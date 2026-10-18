<?php

/**
 * Khaneh Romantic — Admin login (branded split screen)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Same Filament login (rate limit, remember me, password reset) in the store's own layout:
 * the form on one side, the brand panel on the other (hidden on phones).
 */

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $layout = 'filament.auth.layout';

    public function getTitle(): string|Htmlable
    {
        return 'ورود به پنل مدیریت';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'خوش برگشتید 👋';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'برای مدیریت فروشگاه وارد شوید.';
    }

    public function hasLogo(): bool
    {
        return false; // the layout shows the brand
    }
}
