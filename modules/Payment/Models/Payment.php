<?php

/**
 * Khaneh Romantic — One payment attempt for an order
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Models\Order;

class Payment extends Model
{
    public const STATUSES = [
        'pending' => 'در انتظار پرداخت',
        'review' => 'در انتظار تأیید',
        'paid' => 'موفق',
        'failed' => 'ناموفق',
        'rejected' => 'رد شده',
        'cancelled' => 'لغو شده',
    ];

    public const COLORS = ['pending' => 'warning', 'review' => 'info', 'paid' => 'success', 'failed' => 'danger', 'rejected' => 'danger', 'cancelled' => 'gray'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'meta' => 'array', 'paid_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
