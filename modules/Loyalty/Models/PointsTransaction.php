<?php

/**
 * Khaneh Romantic — One points ledger entry (append-only)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Customer\Models\Customer;

class PointsTransaction extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = ['earn' => 'خرید', 'redeem' => 'تبدیل به اعتبار', 'adjustment' => 'تغییر توسط فروشگاه', 'reversal' => 'برگشت (لغو/استرداد)'];

    protected $table = 'loyalty_points_transactions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'balance_before' => 'integer', 'balance_after' => 'integer', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Points ledger entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Points ledger entries are immutable.'));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
