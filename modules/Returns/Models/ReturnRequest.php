<?php

/**
 * Khaneh Romantic — A return request (مرجوعی)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;

class ReturnRequest extends Model
{
    public const STATUSES = [
        'requested' => 'در انتظار بررسی',
        'approved' => 'تأیید شد — کالا را بفرستید',
        'received' => 'کالا رسید',
        'refunded' => 'مبلغ برگشت داده شد',
        'rejected' => 'رد شد',
        'cancelled' => 'لغو شد',
    ];

    public const COLORS = ['requested' => 'warning', 'approved' => 'info', 'received' => 'primary', 'refunded' => 'success', 'rejected' => 'danger', 'cancelled' => 'gray'];

    /** still in progress: its items can't be requested again */
    public const OPEN = ['requested', 'approved', 'received'];

    public const REASONS = [
        'damaged' => 'آسیب‌دیده یا شکسته رسید',
        'wrong' => 'کالای اشتباه فرستاده شد',
        'defective' => 'ایراد فنی یا کارخانه‌ای',
        'not_as_described' => 'با توضیحات و عکس سایت فرق دارد',
        'changed_mind' => 'منصرف شدم',
        'other' => 'دلیل دیگر',
    ];

    /** reasons where a photo shows the problem */
    public const PHOTO_REASONS = ['damaged', 'wrong', 'defective', 'not_as_described'];

    public const BADGE_KEY = 'admin.badge.returns';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['photos' => 'array', 'approved_at' => 'datetime', 'received_at' => 'datetime', 'refunded_at' => 'datetime', 'refund_amount' => 'integer'];
    }

    protected static function booted(): void
    {
        $refresh = fn () => Cache::forget(self::BADGE_KEY);
        static::saved($refresh);
        static::deleted($refresh);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /** Value of the returned lines (what the customer paid for them, Rials). */
    public function itemsValue(): int
    {
        return (int) $this->items->sum(fn (ReturnItem $i) => $i->orderItem ? intdiv($i->orderItem->total * $i->quantity, max(1, $i->orderItem->quantity)) : 0);
    }
}
