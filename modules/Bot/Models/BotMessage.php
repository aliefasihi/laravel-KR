<?php

/**
 * Khaneh Romantic — The bot's history (both apps, both directions)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotMessage extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(BotOperator::class, 'operator_id');
    }
}
