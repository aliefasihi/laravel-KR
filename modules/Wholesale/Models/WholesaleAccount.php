<?php

/**
 * Khaneh Romantic — A wholesale customer (همکار): the application and its status
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Modules\Customer\Models\Customer;

class WholesaleAccount extends Model
{
    public const STATUSES = ['pending' => 'در انتظار بررسی', 'approved' => 'همکار', 'rejected' => 'رد شد', 'suspended' => 'متوقف'];

    public const COLORS = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'suspended' => 'gray'];

    public const TYPES = [
        'shop' => 'مغازه (لوازم خانگی، کادویی، دکوری…)',
        'online' => 'فروشگاه اینترنتی یا پیج',
        'decorator' => 'طراح داخلی یا دکوراتور',
        'company' => 'شرکت یا سازمان (هدیه‌ی سازمانی)',
        'other' => 'سایر',
    ];

    public const BADGE_KEY = 'admin.badge.wholesale';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        $refresh = fn () => Cache::forget(self::BADGE_KEY);
        static::saved($refresh);
        static::deleted($refresh);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
