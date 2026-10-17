<?php

/**
 * Khaneh Romantic — Attach media to any model
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Media\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Media\Models\Media;

trait HasMedia
{
    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot(['collection', 'position'])
            ->orderBy('mediables.position');
    }

    public function mediaIn(string $collection): MorphToMany
    {
        return $this->media()->wherePivot('collection', $collection);
    }
}
