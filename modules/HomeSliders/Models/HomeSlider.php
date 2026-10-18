<?php

/**
 * Khaneh Romantic — One product slider on the home page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\HomeSliders\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Support\ProductListing;

class HomeSlider extends Model
{
    use FlushesStoreCache;

    public const SOURCES = [
        'newest' => 'جدیدترین محصولات',
        'sale' => 'محصولات تخفیف‌دار',
        'category' => 'یک دسته‌بندی',
        'brand' => 'یک برند',
    ];

    public const SORTS = [
        'newest' => 'جدیدترین',
        'discount' => 'بیشترین تخفیف',
        'price_asc' => 'ارزان‌ترین',
        'price_desc' => 'گران‌ترین',
    ];

    public const STYLES = [
        'glass' => 'ساده (کارت‌های شیشه‌ای با عنوان)',
        'band' => 'نوار رنگی با شمارش معکوس (مثل شگفت‌انگیزها)',
    ];

    /** Where on the home page (between the fixed sections). */
    public const SLOTS = [
        'after_hero' => 'بعد از بنر اصلی',
        'after_categories' => 'بعد از دایره‌های دسته‌بندی',
        'after_banners' => 'بعد از دو بنر کنار هم',
        'after_brands' => 'بعد از لوگوی برندها',
        'before_journal' => 'قبل از مقالات',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'only_in_stock' => 'boolean',
            'only_sale' => 'boolean',
            'autoplay' => 'boolean',
            'is_active' => 'boolean',
            'limit' => 'integer',
            'interval' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return Collection<int, Product> */
    public function products(): Collection
    {
        $listing = app(ProductListing::class);
        $categoryIds = $this->source === 'category' && $this->category ? $this->category->descendantIds() : null;
        $brandId = $this->source === 'brand' ? $this->brand_id : null;

        if (($this->source === 'category' && ! $this->category) || ($this->source === 'brand' && ! $brandId)) {
            return new Collection;
        }

        return $listing->apply($listing->base($categoryIds, $brandId), [
            'brand' => [],
            'in_stock' => $this->only_in_stock,
            'sale' => $this->source === 'sale' || $this->only_sale,
            'min' => null,
            'max' => null,
            'sort' => $this->sort_by,
        ])->limit(max(4, min(40, $this->limit)))->get();
    }

    /** «مشاهده همه»: the listing these products come from. */
    public function url(): string
    {
        $query = array_filter([
            'sort' => $this->sort_by === 'newest' ? null : $this->sort_by,
            'sale' => $this->source === 'sale' || $this->only_sale ? 1 : null,
            'in_stock' => $this->only_in_stock && $this->source !== 'newest' ? 1 : null,
        ]);
        $suffix = $query ? '?'.http_build_query($query) : '';

        return match ($this->source) {
            'category' => $this->category ? $this->category->url().$suffix : route('catalog.shop', $query),
            'brand' => $this->brand ? $this->brand->url().$suffix : route('catalog.shop', $query),
            default => route('catalog.shop', $query),
        };
    }
}
