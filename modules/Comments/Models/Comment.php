<?php

/**
 * Khaneh Romantic — A comment (under a blog post), possibly a reply
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Comments\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;

class Comment extends Model
{
    public const STATUSES = ['pending' => 'در انتظار تأیید', 'approved' => 'تأییدشده', 'rejected' => 'ردشده', 'spam' => 'هرزنامه'];

    public const COLORS = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'gray', 'spam' => 'danger'];

    public const BADGE_KEY = 'admin.badge.comments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean', 'likes' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::BADGE_KEY));
        static::deleted(fn () => Cache::forget(self::BADGE_KEY));
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest('id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
