<?php

/**
 * Khaneh Romantic — An allowed value of an attribute (استیل، طلایی، ۲۸)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Attributes\Support\VariantOptions;

class AttributeValue extends Model
{
    use FlushesStoreCache;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // models store their choice by label: «طلایی» → «طلایی براق» follows into them
        static::updated(function (AttributeValue $value) {
            if ($value->wasChanged('value') && ($attribute = $value->attribute()->value('name'))) {
                VariantOptions::renameValue((string) $attribute, (string) $value->getOriginal('value'), $value->value);
            }
        });
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }
}
