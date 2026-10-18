<?php

/**
 * Khaneh Romantic — A 301 the system proposes for an old URL (approve in the admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Models;

use App\Core\Support\UrlPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;

class RedirectSuggestion extends Model
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const STATUSES = [self::PENDING => 'در انتظار بررسی', self::APPROVED => 'تأیید شد', self::REJECTED => 'رد شد'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['score' => 'float', 'hits' => 'integer', 'last_hit_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (RedirectSuggestion $s) {
            $s->from_hash = UrlPath::hash($s->from_path);
            $s->from_path = UrlPath::normalize($s->from_path);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** Create the 301 (optionally to another product than the suggested one). */
    public function approve(?Product $product = null): Redirect
    {
        $product ??= $this->product;
        $to = Product::pathFor($product->slug);

        $redirect = Redirect::point($this->from_path, $to, 'suggestion');
        $this->update(['status' => self::APPROVED, 'product_id' => $product->id, 'to_path' => $to]);

        return $redirect;
    }

    public function reject(): void
    {
        $this->update(['status' => self::REJECTED]);
    }
}
