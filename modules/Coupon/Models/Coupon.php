<?php

/**
 * Khaneh Romantic — A discount code
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Models;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Coupon extends Model
{
    public const TYPES = ['percent' => 'درصدی', 'fixed' => 'مبلغ ثابت'];

    protected $guarded = ['id', 'used_count'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'max_discount' => 'integer',
            'min_subtotal' => 'integer',
            'max_subtotal' => 'integer',
            'free_shipping' => 'boolean',
            'first_order_only' => 'boolean',
            'exclude_sale_items' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'product_ids' => 'array',
            'exclude_product_ids' => 'array',
            'category_ids' => 'array',
            'exclude_category_ids' => 'array',
            'brand_ids' => 'array',
            'allowed_mobiles' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Coupon $coupon) => $coupon->code = self::normalize($coupon->code));
    }

    /** "  yalda-1405 " / "یلدا۱۴۰۵" → "YALDA-1405" / "یلدا1405" */
    public static function normalize(?string $code): string
    {
        return Str::upper(trim(PersianText::toLatinDigits((string) $code)));
    }

    public static function findByCode(?string $code): ?self
    {
        return $code ? static::query()->where('code', self::normalize($code))->first() : null;
    }

    public static function generateCode(int $length = 8): string
    {
        do {
            $code = Str::upper(Str::random($length));
        } while (static::query()->where('code', $code)->exists() || preg_match('/[0O1I]/', $code));

        return $code;
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /** "۱۵٪ تا سقف ۵۰۰٬۰۰۰ تومان" / "۲۰۰٬۰۰۰ تومان" / "ارسال رایگان" */
    public function summary(): string
    {
        $parts = [];
        if ($this->amount > 0) {
            $parts[] = $this->type === 'percent'
                ? PersianText::toPersianDigits($this->amount).'٪'.($this->max_discount ? ' تا سقف '.Money::toman($this->max_discount).' تومان' : '')
                : Money::toman($this->amount).' تومان';
        }
        if ($this->free_shipping) {
            $parts[] = 'ارسال رایگان';
        }

        return implode(' + ', $parts) ?: '—';
    }
}
