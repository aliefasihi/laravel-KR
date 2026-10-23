<?php

/**
 * Khaneh Romantic — A person who runs the shop from the bot (one row for both Bale and Telegram)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotOperator extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['context' => 'array', 'notify_orders' => 'boolean', 'is_active' => 'boolean', 'link_expires_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(BotDraft::class, 'draft_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(BotDraft::class, 'operator_id');
    }

    /** @return array<string, string> channel => chat id, for every app this person linked */
    public function chats(): array
    {
        return array_filter(['telegram' => $this->telegram_chat_id, 'bale' => $this->bale_chat_id]);
    }

    public function newLinkCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (self::query()->where('link_code', $code)->exists());
        $this->update(['link_code' => $code, 'link_expires_at' => now()->addMinutes(30)]);

        return $code;
    }

    public function ctx(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /** moves the conversation on (state + context, merged) */
    public function goTo(string $state, array $context = [], bool $merge = false): void
    {
        $this->forceFill(['state' => $state, 'context' => $merge ? array_merge((array) $this->context, $context) : $context])->save();
    }
}
