<?php

/**
 * Khaneh Romantic — Product (URL: /p/{brand}-{sku})
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Events\ProductUrlChanged;
use Modules\Catalog\Support\ProductSlug;
use Modules\Media\Concerns\HasMedia;

class Product extends Model
{
    use FlushesStoreCache, HasMedia, SoftDeletes;

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
        // The slug is always derived from brand + SKU; it is never set by hand. No SKU: a draft
        // without an address, which can't be published until the SKU is entered.
        static::saving(function (Product $product) {
            $product->sku = filled($product->sku) ? trim((string) $product->sku) : null;
            if ($product->sku === null) {
                if ($product->status === self::STATUS_ACTIVE) {
                    throw ValidationException::withMessages(['sku' => 'بدون کد کالا (SKU) محصول منتشر نمی‌شود؛ اول SKU را وارد کنید.']);
                }
                $product->slug = null;

                return;
            }

            $brand = $product->relationLoaded('brand') && $product->brand?->getKey() == $product->brand_id
                ? $product->brand
                : Brand::query()->findOrFail($product->brand_id);

            $product->slug = ProductSlug::make($brand->slug, $product->sku);
        });

        // models saved before the product had a SKU take it now (first model = the product's SKU)
        static::saved(function (Product $product) {
            if ($product->sku !== null && $product->wasChanged('sku')) {
                $product->variants()->whereNull('sku')->orderBy('id')->get()->each(function (ProductVariant $variant, int $i) use ($product) {
                    $base = $i === 0 ? $product->sku : $product->sku.'-'.($i + 1);
                    $sku = $base;
                    for ($n = 2; ProductVariant::query()->where('sku', $sku)->exists(); $n++) {
                        $sku = $base.'-'.$n;
                    }
                    $variant->update(['sku' => $sku]);
                });
            }
        });

        static::updated(function (Product $product) {
            // a first SKU gives the product its first address: nothing to redirect from
            if ($product->wasChanged('slug') && $product->getOriginal('slug') !== null && $product->slug !== null) {
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

    /** null while the product has no SKU (and so no address) */
    public function url(): ?string
    {
        return $this->slug === null ? null : route('catalog.product', $this->slug);
    }

    /**
     * What a product card shows (every card design): the cheapest variant in stock (null = sold out)
     * and whether one tap can add it (a single active variant). Uses the loaded variants.
     *
     * @return array{variant: ?ProductVariant, single: bool}
     */
    public function cardSummary(): array
    {
        $active = $this->variants->where('is_active', true);
        $variant = $active->where('stock', '>', 0)->sortBy(fn (ProductVariant $v) => $v->currentPrice())->first();

        return ['variant' => $variant, 'single' => $active->count() === 1 && $variant !== null];
    }
}
