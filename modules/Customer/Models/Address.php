<?php

/**
 * Khaneh Romantic — A delivery address of a customer
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Concerns\HasPostalAddress;

class Address extends Model
{
    use HasPostalAddress;

    protected $table = 'customer_addresses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    protected static function booted(): void
    {
        // one default per customer; the first address is the default
        static::saving(function (Address $address) {
            if (! $address->is_default && ! static::query()->where('customer_id', $address->customer_id)->whereKeyNot($address->id)->exists()) {
                $address->is_default = true;
            }
        });
        static::saved(function (Address $address) {
            if ($address->is_default) {
                static::query()->where('customer_id', $address->customer_id)->whereKeyNot($address->id)->update(['is_default' => false]);
            }
        });
        static::deleted(function (Address $address) {
            if ($address->is_default) {
                static::query()->where('customer_id', $address->customer_id)->latest('id')->first()?->update(['is_default' => true]);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** One line for lists and invoices. */
    public function line(): string
    {
        return $this->addressLine();
    }
}
