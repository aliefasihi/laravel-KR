<?php

/**
 * Khaneh Romantic — An attribute inside a group, with its flags
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeGroupItem extends Model
{
    use FlushesStoreCache;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'is_variation' => 'boolean', 'is_multiple' => 'boolean', 'sort' => 'integer'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'group_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }
}
