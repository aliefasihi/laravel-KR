<?php

/**
 * Khaneh Romantic — A product being put together in the bot (photos, texts, fields) until published
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Product;

class BotDraft extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array', 'photos' => 'array', 'locked' => 'array'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (BotDraft $draft) => Storage::disk('local')->deleteDirectory('bot/drafts/'.$draft->id));
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(BotOperator::class, 'operator_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /** sets fields; $byHand = the operator answered/edited them (AI won't overwrite) */
    public function put(array $values, bool $byHand = false): void
    {
        $data = (array) $this->data;
        foreach ($values as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $data[$key] = $value;
        }
        $this->data = $data;
        if ($byHand) {
            $this->locked = array_values(array_unique(array_merge((array) $this->locked, array_keys($values))));
        }
        $this->save();
    }

    public function isLocked(string $key): bool
    {
        return in_array($key, (array) $this->locked, true);
    }

    /** @return list<string> absolute paths of the photos, in order */
    public function photoPaths(): array
    {
        return array_values(array_filter(array_map(fn ($p) => Storage::disk('local')->path($p['path']), (array) $this->photos), 'is_file'));
    }

    public function title(): string
    {
        return $this->get('name') ?: 'محصول بی‌نام #'.$this->id;
    }
}
