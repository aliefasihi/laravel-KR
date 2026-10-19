<?php

/**
 * Khaneh Romantic — A customer level (برنزی، نقره‌ای، طلایی…) reached by total completed purchases
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Loyalty\Support\Levels;

class Level extends Model
{
    public const CACHE_KEY = 'loyalty.levels';

    protected $table = 'loyalty_levels';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['min_spent' => 'integer', 'points_multiplier' => 'float', 'is_active' => 'boolean', 'sort' => 'integer'];
    }

    protected static function booted(): void
    {
        $changed = function () {
            Cache::forget(self::CACHE_KEY);
            Levels::refreshAll();
        };
        static::saved($changed);
        static::deleted($changed);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return list<string> */
    public function perkList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $this->perks))));
    }
}
