<?php

/**
 * Khaneh Romantic — A customer's question about a product (and the shop's answer)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Questions\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Models\Product;

class Question extends Model
{
    use FlushesStoreCache;

    public const STATUSES = ['pending' => 'در انتظار پاسخ', 'published' => 'منتشرشده', 'rejected' => 'ردشده'];

    public const COLORS = ['pending' => 'warning', 'published' => 'success', 'rejected' => 'gray'];

    public const BADGE_KEY = 'admin.badge.questions';

    protected $table = 'product_questions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime', 'notify' => 'boolean'];
    }

    protected static function booted(): void
    {
        $refresh = fn () => Cache::forget(self::BADGE_KEY);
        static::saved($refresh);
        static::deleted($refresh);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('answer');
    }
}
