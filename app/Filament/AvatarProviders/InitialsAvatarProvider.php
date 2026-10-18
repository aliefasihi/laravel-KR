<?php

/**
 * Khaneh Romantic — Admin avatars drawn locally (inline SVG)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Filament's default avatar is fetched from ui-avatars.com on every page. From Iran that
 * request is slow or filtered and holds up the panel, so the initials are rendered here.
 */

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(fn (string $part) => mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', $part) ?? '', 0, 1))
            ->filter()
            ->take(2)
            ->join('‌');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#23284f"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#fff" font-family="Vazirmatn, sans-serif" font-size="26" font-weight="700">'
            .e($initials ?: '؟')
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
