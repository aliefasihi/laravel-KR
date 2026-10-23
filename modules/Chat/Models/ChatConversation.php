<?php

/**
 * Khaneh Romantic — A visitor's conversation with the site assistant
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Modules\Customer\Models\Customer;

class ChatConversation extends Model
{
    public const BADGE_KEY = 'admin.badge.chat';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['context' => 'array', 'handoff' => 'boolean', 'unanswered' => 'boolean', 'last_message_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::BADGE_KEY));
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
