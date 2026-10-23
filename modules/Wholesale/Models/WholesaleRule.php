<?php

/**
 * Khaneh Romantic — Wholesale price rule for a category, a brand or one product
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * tiers: [{min_qty: 1, percent: 10}, {min_qty: 12, percent: 15}] — the percent below the regular
 * price from that many of one model in the cart. No tiers = no wholesale price for these products.
 */

namespace Modules\Wholesale\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class WholesaleRule extends Model
{
    use FlushesStoreCache;

    public const SCOPES = ['category' => 'دسته‌بندی', 'brand' => 'برند', 'product' => 'یک محصول'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tiers' => 'array', 'is_active' => 'boolean'];
    }

    public function targetName(): string
    {
        return (string) match ($this->scope) {
            'category' => Category::query()->whereKey($this->target_id)->value('name'),
            'brand' => Brand::query()->whereKey($this->target_id)->value('name_fa'),
            'product' => Product::query()->whereKey($this->target_id)->value('name'),
            default => '',
        };
    }
}
