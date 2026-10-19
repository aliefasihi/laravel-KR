<?php

/**
 * Khaneh Romantic — A customer's review of a product
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Reviews\Models;

use App\Core\Support\StoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;
use Modules\Customer\Models\Customer;

class Review extends Model
{
    public const STATUSES = ['pending' => 'در انتظار تأیید', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'];

    public const COLORS = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'gray'];

    protected $table = 'product_reviews';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_verified_buyer' => 'boolean', 'helpful' => 'integer', 'replied_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        $refresh = fn (Review $review) => self::refreshProduct($review->product_id);
        static::saved($refresh);
        static::deleted($refresh);
    }

    /** Product's average/count from approved reviews; the cached pages show the new stars. */
    public static function refreshProduct(int $productId): void
    {
        $stats = DB::table('product_reviews')->where('product_id', $productId)->where('status', 'approved')
            ->selectRaw('count(*) as c, avg(rating) as a')->first();

        Product::query()->whereKey($productId)->update([
            'rating_count' => (int) $stats->c,
            'rating_avg' => $stats->c ? round((float) $stats->a, 2) : null,
        ]);
        StoreCache::flush();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
