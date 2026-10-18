<?php

/**
 * Khaneh Romantic — A shopper (logs in with mobile + SMS code)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Models;

use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    protected $guarded = ['id', 'remember_token'];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'newsletter' => 'boolean',
            'mobile_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->latest('id');
    }

    public function defaultAddress(): ?Address
    {
        return $this->addresses()->first();
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** «علی فصیحی» or the number (۰۹۱۲…۴۵۶۷) for customers without a name yet. */
    public function displayName(): string
    {
        return $this->fullName() ?: PersianText::toPersianDigits($this->mobile);
    }

    public function getAuthPassword(): string
    {
        return ''; // no passwords: OTP only
    }
}
