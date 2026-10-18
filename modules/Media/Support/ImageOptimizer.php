<?php

/**
 * Khaneh Romantic — Responsive WebP/AVIF versions for every image (GD, no extra packages)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Imported WooCommerce images are often 1–3 MB originals; showing those on a product card is
 * what makes a store slow. For each Media we write resized copies next to the original:
 *
 *   media/conversions/{id}/{name}-320.webp, -640.webp, -960.webp, -1440.webp  (+ .avif when GD supports it)
 *
 * and record them in media.variants so <x-media.picture> can emit a srcset and the browser
 * downloads only the size it actually needs.
 */

namespace Modules\Media\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Models\Media;
use Throwable;

class ImageOptimizer
{
    /** Target widths (px). Only widths smaller than the original are produced. */
    public const WIDTHS = [320, 640, 960, 1440];

    private const WEBP_QUALITY = 80;

    private const AVIF_QUALITY = 55;

    /** Refuse absurd images instead of exhausting memory (≈ 50 megapixels). */
    private const MAX_PIXELS = 50_000_000;

    public static function supported(): bool
    {
        return function_exists('imagewebp') && (imagetypes() & IMG_WEBP);
    }

    public static function avifSupported(): bool
    {
        return function_exists('imageavif') && (imagetypes() & (defined('IMG_AVIF') ? IMG_AVIF : 0));
    }

    /** Create (or re-create) the responsive versions. Returns false when the file can't be processed. */
    public function optimize(Media $media): bool
    {
        if (! self::supported() || ! $media->path || ! str_starts_with((string) $media->mime, 'image/')) {
            return false;
        }

        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path) || in_array($media->mime, ['image/svg+xml', 'image/gif'], true)) {
            return false;
        }

        $source = $this->load($disk->path($media->path), (string) $media->mime);

        if (! $source) {
            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $dir = self::directoryFor($media);
        $name = Str::slug(pathinfo($media->path, PATHINFO_FILENAME)) ?: 'image';

        $disk->deleteDirectory($dir);
        $disk->makeDirectory($dir);

        $widths = array_values(array_filter(self::WIDTHS, fn (int $w) => $w < $width));
        if ($width <= max(self::WIDTHS) && ! in_array($width, $widths, true)) {
            $widths[] = $width; // the original size itself, re-encoded
        }

        $variants = ['webp' => [], 'avif' => []];

        foreach ($widths as $w) {
            $h = (int) max(1, round($height * $w / $width));
            $resized = $w === $width ? $source : $this->resize($source, $w, $h);

            foreach (['webp' => self::supported(), 'avif' => self::avifSupported()] as $format => $enabled) {
                if (! $enabled) {
                    continue;
                }

                $path = "{$dir}/{$name}-{$w}.{$format}";
                $ok = $format === 'webp'
                    ? imagewebp($resized, $disk->path($path), self::WEBP_QUALITY)
                    : imageavif($resized, $disk->path($path), self::AVIF_QUALITY);

                if ($ok) {
                    $variants[$format][] = ['w' => $w, 'h' => $h, 'path' => $path, 'size' => $disk->size($path)];
                }
            }

            if ($resized !== $source) {
                imagedestroy($resized);
            }
        }

        imagedestroy($source);

        $media->variants = array_filter($variants) + ['optimized_at' => now()->toIso8601String()];
        $media->width ??= $width;
        $media->height ??= $height;
        $media->save();

        return true;
    }

    /** Remove generated files (when the media is deleted or replaced). */
    public function purge(Media $media): void
    {
        Storage::disk($media->disk ?: 'public')->deleteDirectory(self::directoryFor($media));
    }

    public static function directoryFor(Media $media): string
    {
        return 'media/conversions/'.$media->getKey();
    }

    private function load(string $file, string $mime): ?GdImage
    {
        $info = @getimagesize($file);
        if (! $info || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        try {
            $image = match ($mime) {
                'image/jpeg', 'image/jpg', 'image/pjpeg' => @imagecreatefromjpeg($file),
                'image/png' => @imagecreatefrompng($file),
                'image/webp' => @imagecreatefromwebp($file),
                'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($file) : false,
                'image/bmp', 'image/x-ms-bmp' => @imagecreatefrombmp($file),
                default => false,
            };
        } catch (Throwable) {
            return null;
        }

        if (! $image instanceof GdImage) {
            return null;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $this->orient($image, $file, $mime);
    }

    /** Phone photos store rotation in EXIF; bake it in so the WebP isn't sideways. */
    private function orient(GdImage $image, string $file, string $mime): GdImage
    {
        if (! str_contains($mime, 'jpeg') || ! function_exists('exif_read_data')) {
            return $image;
        }

        $angle = match ((int) (@exif_read_data($file)['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if ($rotated instanceof GdImage) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function resize(GdImage $source, int $w, int $h): GdImage
    {
        $target = imagecreatetruecolor($w, $h);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 255, 255, 255, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $w, $h, imagesx($source), imagesy($source));

        return $target;
    }
}
