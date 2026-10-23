<?php

/**
 * Khaneh Romantic — One bulk price change (with the old prices, for «برگرداندن»)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\BulkEdit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceChange extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rows' => 'array', 'undone_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
