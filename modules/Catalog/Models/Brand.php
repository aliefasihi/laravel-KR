<?php

/**
 * Khaneh Romantic — Brand
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Support\ProductSlug;
use Modules\Media\Concerns\HasMedia;

class Brand extends Model
{
    use HasMedia;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (Brand $brand) {
            $brand->slug = ProductSlug::clean($brand->slug ?: $brand->name_en);
        });

        // Brand slug is part of every product URL → re-derive them (each fires ProductUrlChanged).
        static::updated(function (Brand $brand) {
            if ($brand->wasChanged('slug')) {
                $brand->products()->each(fn (Product $product) => $product->setRelation('brand', $brand)->save());
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function url(): string
    {
        return route('catalog.brand', $this->slug);
    }
}
