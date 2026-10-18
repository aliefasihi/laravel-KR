<?php

/**
 * Khaneh Romantic — A folder in the file manager
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Media\Models\Media;

class MediaFolder extends Model
{
    public const ROOT = 'root';

    public const BRAND = 'brand';

    public const PRODUCT = 'product';

    public const CUSTOM = 'custom';

    protected $guarded = ['id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function files(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id')->latest('id');
    }

    /** Storage directory (public disk). */
    public function directory(): string
    {
        return 'media/'.$this->path;
    }

    /** Folders the system manages (root/brand/product) are renamed and moved only by the system. */
    public function isSystem(): bool
    {
        return $this->kind !== self::CUSTOM;
    }

    /** @return list<self> from the top folder down to this one */
    public function trail(): array
    {
        $trail = [];
        $folder = $this;
        $guard = 0;
        while ($folder && $guard++ < 20) {
            array_unshift($trail, $folder);
            $folder = $folder->parent;
        }

        return $trail;
    }
}
