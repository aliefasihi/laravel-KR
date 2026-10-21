<?php

/**
 * Khaneh Romantic — One product on a list, how many are wanted, and the claims on it
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Models\Product;

class RegistryItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function registry(): BelongsTo
    {
        return $this->belongsTo(Registry::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(RegistryClaim::class);
    }

    public function claimed(): int
    {
        return (int) $this->claims->sum('quantity');
    }

    public function remaining(): int
    {
        return max(0, $this->quantity - $this->claimed());
    }
}
