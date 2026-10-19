<?php

/**
 * Khaneh Romantic — A customer's wallet (store credit). Balance changes only through Support\Wallet.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;

class Wallet extends Model
{
    protected $table = 'loyalty_wallets';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['balance' => 'integer'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('id');
    }
}
