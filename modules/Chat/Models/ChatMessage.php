<?php

/**
 * Khaneh Romantic — One message in a site chat
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /** what the widget shows */
    public function toWidget(): array
    {
        return ['role' => $this->role, 'text' => (string) $this->text] + array_intersect_key((array) $this->meta, array_flip(['products', 'quick', 'links']));
    }
}
