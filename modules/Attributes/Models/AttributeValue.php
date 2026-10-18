<?php

/**
 * Khaneh Romantic — An allowed value of an attribute (استیل، طلایی، ۲۸)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    use FlushesStoreCache;

    protected $guarded = ['id'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }
}
