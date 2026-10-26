<?php

/**
 * Khaneh Romantic — Collection: a design line across categories (URL: /collections/{slug})
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Collections\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Catalog\Models\Product;
use Modules\Media\Concerns\HasMedia;

class Collection extends Model
{
    use FlushesStoreCache, HasMedia;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Active and with at least one published product: the ones the site shows. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereHas('products', fn ($q) => $q->active());
    }

    public function url(): string
    {
        return route('collections.show', $this->slug);
    }
}
