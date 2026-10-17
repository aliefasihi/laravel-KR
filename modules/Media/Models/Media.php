<?php

/**
 * Khaneh Romantic — Media model
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
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
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
