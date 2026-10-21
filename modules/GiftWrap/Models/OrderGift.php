<?php

/**
 * Khaneh Romantic — Gift options of an order
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\GiftWrap\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGift extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['wrap' => 'boolean', 'hide_prices' => 'boolean', 'fee' => 'integer'];
    }
}
