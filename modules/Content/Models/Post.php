<?php

/**
 * Khaneh Romantic — Blog post
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Media\Concerns\HasMedia;

class Post extends Model
{
    use HasMedia;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['content_blocks' => 'array', 'published_at' => 'datetime'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)->where('published_at', '<=', now());
    }

    public function url(): string
    {
        return route('content.post', $this->slug);
    }
}
