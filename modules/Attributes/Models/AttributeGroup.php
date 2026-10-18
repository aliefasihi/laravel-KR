<?php

/**
 * Khaneh Romantic — An ordered set of attributes for a kind of product (e.g. «قابلمه و دیگ»)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Models\Category;

class AttributeGroup extends Model
{
    use FlushesStoreCache;

    protected $guarded = ['id'];

    public function items(): HasMany
    {
        return $this->hasMany(AttributeGroupItem::class, 'group_id')->orderBy('sort')->orderBy('id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'attribute_group_category', 'group_id', 'category_id');
    }
}
