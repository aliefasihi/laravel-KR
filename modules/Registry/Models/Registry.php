<?php

/**
 * Khaneh Romantic — A shopping list to share (wedding «جهیزیه», housewarming, …)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;

class Registry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'is_public' => 'boolean', 'show_buyers' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Registry $r) => $r->token ??= Str::lower(Str::random(10)));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RegistryItem::class)->orderBy('sort')->orderBy('id');
    }

    public function url(): string
    {
        return route('registry.show', $this->token);
    }
}
