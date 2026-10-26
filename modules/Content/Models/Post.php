<?php

/**
 * Khaneh Romantic — Blog post
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Media\Concerns\HasMedia;

class Post extends Model
{
    use FlushesStoreCache, HasMedia;

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function url(): string
    {
        return route('content.post', $this->slug);
    }
}
