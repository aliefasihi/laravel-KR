<?php

/**
 * Khaneh Romantic — Banner
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Media\Models\Media;

class Banner extends Model
{
    use FlushesStoreCache;

    public const STATUSES = ['draft' => 'پیش‌نویس', 'approved' => 'تأییدشده (نمایش)', 'archived' => 'بایگانی'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function desktopMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'desktop_media_id');
    }

    public function mobileMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'mobile_media_id');
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
