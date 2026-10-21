<?php

/**
 * Khaneh Romantic — «I bought / I'll buy this» on a list item
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistryClaim extends Model
{
    public const KINDS = ['reserved' => 'رزرو کرد', 'ordered' => 'از سایت خرید'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(RegistryItem::class, 'registry_item_id');
    }
}
