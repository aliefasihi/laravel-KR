<?php

/**
 * Khaneh Romantic — A set of products sold together for less
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bundles\Models;

use App\Core\Support\StoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'discount_percent' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => StoreCache::flush());
        static::deleted(fn () => StoreCache::flush());
    }

    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class)->orderBy('sort')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
