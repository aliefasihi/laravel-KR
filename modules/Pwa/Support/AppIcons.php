<?php

/**
 * Khaneh Romantic — The app's icons
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * An icon uploaded in تنظیمات ← اپلیکیشن (PWA) is cut into the sizes phones ask for (once, into
 * storage/app/public/pwa/); without one, the bundled icons in public/pwa/ are used. The «maskable»
 * one (Android crops it into a circle/squircle) keeps the picture inside the safe zone on the
 * app's colour.
 */

namespace Modules\Pwa\Support;

use App\Core\Settings\Settings;
use App\Core\Support\Designs;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class AppIcons
{
    public const SIZES = ['192' => 192, '512' => 512, 'maskable-512' => 512, 'apple' => 180];

    /** @return array<string, string> variant => URL */
    public static function urls(): array
    {
        $defaults = [
            '192' => '/pwa/icon-192.png',
            '512' => '/pwa/icon-512.png',
            'maskable-512' => '/pwa/icon-maskable-512.png',
            'apple' => '/pwa/apple-touch-icon.png',
        ];

        $source = Settings::get('pwa.icon');
        if (! is_string($source) || $source === '' || ! Storage::disk('public')->exists($source)) {
            return $defaults;
        }

        $disk = Storage::disk('public');
        $hash = substr(md5($source.'|'.$disk->lastModified($source).'|'.Designs::themeColor()), 0, 10);
        $urls = [];
        foreach (self::SIZES as $variant => $size) {
            $path = 'pwa/'.$hash.'-'.$variant.'.png';
            if (! $disk->exists($path) && ! self::render($disk->path($source), $disk->path($path), $size, $variant === 'maskable-512')) {
                return $defaults;
            }
            $urls[$variant] = $disk->url($path);
        }

        return $urls;
    }

    /** Square PNG on the app colour (transparent corners filled), the picture at 80% for maskable. */
    private static function render(string $from, string $to, int $size, bool $maskable): bool
    {
        try {
            $data = @file_get_contents($from);
            $image = $data !== false ? @imagecreatefromstring($data) : false;
            if ($image === false) {
                return false;
            }
            $canvas = imagecreatetruecolor($size, $size);
            [$r, $g, $b] = sscanf(Designs::themeColor(), '#%02x%02x%02x');
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, $r, $g, $b));

            $inner = $maskable ? (int) round($size * 0.8) : $size;
            $w = imagesx($image);
            $h = imagesy($image);
            $scale = min($inner / $w, $inner / $h);
            $dw = (int) round($w * $scale);
            $dh = (int) round($h * $scale);
            imagealphablending($canvas, true);
            imagecopyresampled($canvas, $image, (int) (($size - $dw) / 2), (int) (($size - $dh) / 2), 0, 0, $dw, $dh, $w, $h);

            @mkdir(dirname($to), 0755, true);
            $ok = imagepng($canvas, $to, 6);
            imagedestroy($canvas);
            imagedestroy($image);

            return $ok;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
