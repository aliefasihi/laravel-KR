<?php

/**
 * Khaneh Romantic — A person who reached the shop through any form (or a customer account)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Models;

use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;

class Contact extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tags' => 'array', 'utm' => 'array',
            'newsletter_sms' => 'boolean', 'newsletter_email' => 'boolean',
            'unsubscribed_at' => 'datetime', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime',
            'submissions_count' => 'integer',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->latest('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function displayName(): string
    {
        return $this->name ?: ($this->mobile ? PersianText::toPersianDigits($this->mobile) : ($this->email ?: 'بی‌نام'));
    }
}
