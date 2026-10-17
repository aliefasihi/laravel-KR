<?php

/**
 * Khaneh Romantic — Product (URL: /p/{brand}-{sku})
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Catalog\Events\ProductUrlChanged;
use Modules\Catalog\Support\ProductSlug;
use Modules\Media\Concerns\HasMedia;

class Product extends Model
{
    use HasMedia, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT => 'پیش‌نویس',
        self::STATUS_ACTIVE => 'منتشرشده',
        self::STATUS_ARCHIVED => 'بایگانی',
    ];

    protected $guarded = ['id', 'slug'];

    protected function casts(): array
    {
        return ['content_blocks' => 'array'];
    }

    protected static function booted(): void
    {
        // The slug is always derived from brand + SKU; it is never set by hand.
        static::saving(function (Product $product) {
            $brand = $product->relationLoaded('brand') && $product->brand?->getKey() == $product->brand_id
                ? $product->brand
                : Brand::query()->findOrFail($product->brand_id);

            $product->slug = ProductSlug::make($brand->slug, $product->sku);
        });

        static::updated(function (Product $product) {
            if ($product->wasChanged('slug')) {
                ProductUrlChanged::dispatch(
                    $product->getKey(),
                    self::pathFor($product->getOriginal('slug')),
                    self::pathFor($product->slug),
                );
            }
        });
    }

    public static function pathFor(string $slug): string
    {
        return '/'.config('khaneh.urls.product_prefix').'/'.$slug;
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot(['is_primary', 'position']);
    }

    public function primaryCategory(): ?Category
    {
        return $this->categories->firstWhere('pivot.is_primary', true) ?? $this->categories->first();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function url(): string
    {
        return route('catalog.product', $this->slug);
    }
}
