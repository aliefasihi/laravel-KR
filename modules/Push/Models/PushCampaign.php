<?php

/**
 * Khaneh Romantic — A notification sent to many browsers from the panel
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Push\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PushCampaign extends Model
{
    public const AUDIENCES = ['all' => 'همه', 'customers' => 'فقط مشتری‌هایی که وارد شده‌اند', 'guests' => 'فقط مهمان‌ها'];

    public const STATUSES = ['draft' => 'پیش‌نویس', 'scheduled' => 'زمان‌بندی شده', 'sending' => 'در حال ارسال', 'sent' => 'ارسال شد', 'cancelled' => 'لغو شد'];

    public const COLORS = ['draft' => 'gray', 'scheduled' => 'info', 'sending' => 'warning', 'sent' => 'success', 'cancelled' => 'danger'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    /** what the browser receives */
    public function payload(): array
    {
        return array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'url' => route('push.click', ['campaign' => $this->id], absolute: false),
            'image' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'tag' => 'campaign-'.$this->id,
        ]);
    }
}
