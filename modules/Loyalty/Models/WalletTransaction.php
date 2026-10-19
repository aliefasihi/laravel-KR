<?php

/**
 * Khaneh Romantic — One wallet ledger entry (append-only)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Customer\Models\Customer;

class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    public const REASONS = [
        'order_payment' => 'پرداخت سفارش',
        'order_refund' => 'بازگشت از سفارش',
        'refund_to_wallet' => 'استرداد به کیف پول',
        'points_redeem' => 'تبدیل امتیاز',
        'referral_reward' => 'جایزه‌ی معرفی',
        'referral_reversal' => 'برگشت جایزه‌ی معرفی',
        'admin_adjustment' => 'تغییر توسط فروشگاه',
        'gift' => 'هدیه',
    ];

    protected $table = 'loyalty_wallet_transactions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'balance_before' => 'integer', 'balance_after' => 'integer', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Wallet ledger entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Wallet ledger entries are immutable.'));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /** +/− amount for display */
    public function signed(): int
    {
        return $this->type === 'credit' ? $this->amount : -$this->amount;
    }
}
