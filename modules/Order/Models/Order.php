<?php

/**
 * Khaneh Romantic — An order
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Models;

use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Models\Customer;

class Order extends Model
{
    /** WooCommerce-like statuses (+ «ارسال شده», the step Iranian shoppers ask about most). */
    public const STATUSES = [
        'pending' => 'در انتظار پرداخت',
        'on_hold' => 'در انتظار بررسی',
        'processing' => 'در حال پردازش',
        'shipped' => 'ارسال شده',
        'completed' => 'تکمیل شده',
        'cancelled' => 'لغو شده',
        'refunded' => 'مسترد شده',
        'failed' => 'ناموفق',
    ];

    public const COLORS = [
        'pending' => 'warning', 'on_hold' => 'info', 'processing' => 'primary', 'shipped' => 'info',
        'completed' => 'success', 'cancelled' => 'gray', 'refunded' => 'gray', 'failed' => 'danger',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'پرداخت‌نشده',
        'pending_review' => 'در انتظار تأیید پرداخت',
        'paid' => 'پرداخت‌شده',
        'partially_refunded' => 'بخشی مسترد شده',
        'refunded' => 'مسترد شده',
    ];

    /** Statuses whose stock is out of the shop (reserved or sold). */
    public const HOLDS_STOCK = ['pending', 'on_hold', 'processing', 'shipped', 'completed'];

    /** Statuses that count as a sale in reports. */
    public const SALE_STATUSES = ['processing', 'shipped', 'completed', 'refunded'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer', 'sale_savings' => 'integer', 'discount' => 'integer', 'shipping_total' => 'integer',
            'fees_total' => 'integer', 'total' => 'integer', 'refunded_total' => 'integer',
            'shipping_on_delivery' => 'boolean', 'stock_reduced' => 'boolean',
            'paid_at' => 'datetime', 'shipped_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->latest('id');
    }

    public function scopeSales(Builder $query): Builder
    {
        return $query->whereIn('status', self::SALE_STATUSES);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['paid', 'partially_refunded', 'refunded'], true);
    }

    /** The shopper may still pay (online) or cancel it themselves. */
    public function isAwaitingPayment(): bool
    {
        return $this->status === 'pending' && $this->payment_status === 'unpaid';
    }

    public function displayNumber(): string
    {
        return PersianText::toPersianDigits((string) $this->number);
    }

    public function addressLine(): string
    {
        return collect([
            $this->province.'، '.$this->city,
            $this->address,
            $this->plaque ? 'پلاک '.$this->plaque : null,
            $this->unit ? 'واحد '.$this->unit : null,
        ])->filter()->implode('، ');
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function note(string $body, string $type = 'system', ?int $userId = null): OrderNote
    {
        return $this->notes()->create(['body' => $body, 'type' => $type, 'user_id' => $userId, 'created_at' => now()]);
    }
}
