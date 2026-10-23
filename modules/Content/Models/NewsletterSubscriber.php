<?php

/**
 * Khaneh Romantic — Newsletter subscriber (email or Iranian mobile)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    public const CHANNELS = ['sms' => 'پیامک', 'email' => 'ایمیل'];

    public const SOURCES = ['footer' => 'فوتر سایت', 'profile' => 'حساب کاربری', 'checkout' => 'تسویه حساب', 'admin' => 'پنل', 'import' => 'درون‌ریزی', 'exit-popup' => 'پاپ‌آپ خروج'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['unsubscribed_at' => 'datetime'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }
}
