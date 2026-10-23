<?php

/**
 * Khaneh Romantic — One returned line of an order
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Models\OrderItem;

class ReturnItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }
}
