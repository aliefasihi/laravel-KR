<?php

/**
 * Khaneh Romantic — Media model
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Media\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Jobs\OptimizeMedia;
use Modules\Media\Support\ImageOptimizer;

class Media extends Model
{
    use FlushesStoreCache;

    protected $table = 'media';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['variants' => 'array'];
    }

    protected static function booted(): void
    {
        // Fill technical metadata from the stored file (uploads from the panel or the importer).
        static::saving(function (Media $media) {
            $media->disk ??= 'public';

            if ($media->isDirty('path') && filled($media->path)) {
                $disk = Storage::disk($media->disk);

                if ($disk->exists($media->path)) {
                    $media->mime = $disk->mimeType($media->path) ?: null;
                    $media->size = $disk->size($media->path);

                    if ($info = @getimagesize($disk->path($media->path))) {
                        [$media->width, $media->height] = $info;
                    }
                }
            }

            $media->mime ??= 'application/octet-stream';
        });

        // New or replaced file → (re)build the responsive versions in the background.
        static::saved(function (Media $media) {
            if (($media->wasRecentlyCreated || $media->wasChanged('path')) && str_starts_with((string) $media->mime, 'image/')) {
                OptimizeMedia::dispatch($media->id)->afterCommit();
            }
        });

        static::deleted(fn (Media $media) => app(ImageOptimizer::class)->purge($media));
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * "url 320w, url 640w, …" for one format, or null when no versions exist yet.
     * Also reads the older single-file shape {"webp": {"path": …}}.
     */
    public function srcset(string $format): ?string
    {
        $versions = $this->variants[$format] ?? null;

        if (! $versions) {
            return null;
        }

        if (isset($versions['path'])) {
            return Storage::disk($this->disk)->url($versions['path']);
        }

        return collect($versions)
            ->map(fn (array $v) => Storage::disk($this->disk)->url($v['path']).' '.$v['w'].'w')
            ->implode(', ');
    }

    /** Full address with the site's host (og:image, structured data, admin image columns). */
    public function absoluteUrl(): string
    {
        return url($this->url());
    }

    public function absoluteUrlFor(int $width = 320): string
    {
        return url($this->urlFor($width));
    }

    /** Smallest version that is at least $width px wide (for admin thumbnails, og images, …). */
    public function urlFor(int $width = 320): string
    {
        $versions = collect($this->variants['webp'] ?? [])->filter(fn ($v) => is_array($v) && isset($v['w']))->sortBy('w');
        $pick = $versions->first(fn (array $v) => $v['w'] >= $width) ?? $versions->last();

        return $pick ? Storage::disk($this->disk)->url($pick['path']) : $this->url();
    }
}
