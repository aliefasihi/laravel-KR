<?php

/**
 * Khaneh Romantic — Cached total spent + level of a customer (rebuildable from orders)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLevel extends Model
{
    public const CREATED_AT = null;

    protected $table = 'loyalty_customer_levels';

    protected $primaryKey = 'customer_id';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['total_spent' => 'integer'];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
