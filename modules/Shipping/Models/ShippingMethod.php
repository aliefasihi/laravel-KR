<?php

/**
 * Khaneh Romantic — A way to send orders (پست پیشتاز، پیک موتوری، تیپاکس، پس‌کرایه…)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Shipping\Models;

use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'cost_per_extra_item' => 'integer',
            'free_over' => 'integer',
            'min_subtotal' => 'integer',
            'max_subtotal' => 'integer',
            'province_costs' => 'array',
            'provinces' => 'array',
            'cities' => 'array',
            'pay_on_delivery' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    /** Can this method deliver to the address, for this cart total? */
    public function availableFor(?string $province, ?string $city, int $subtotal): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->provinces && $province !== null && ! in_array($province, $this->provinces, true)) {
            return false;
        }
        if ($this->cities && $city !== null && ! in_array(self::normalizeCity($city), array_map(self::normalizeCity(...), $this->cities), true)) {
            return false;
        }
        if (($this->provinces || $this->cities) && $province === null) {
            return false;
        }
        if ($this->min_subtotal && $subtotal < $this->min_subtotal) {
            return false;
        }

        return ! ($this->max_subtotal && $subtotal > $this->max_subtotal);
    }

    /** Cost in Rials for an address and cart (0 for «پس‌کرایه» and above free_over). */
    public function costFor(?string $province, int $subtotal, int $items, bool $freeShipping = false): int
    {
        if ($this->pay_on_delivery || $freeShipping || ($this->free_over && $subtotal >= $this->free_over)) {
            return 0;
        }

        $base = (int) ($this->province_costs[$province] ?? $this->cost);

        return $base + max(0, $items - 1) * $this->cost_per_extra_item;
    }

    private static function normalizeCity(string $city): string
    {
        return str_replace(['ي', 'ك', ' '], ['ی', 'ک', ''], PersianText::toLatinDigits(trim($city)));
    }
}
