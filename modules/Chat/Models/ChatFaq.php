<?php

/**
 * Khaneh Romantic — A ready answer the shop wrote for the site assistant
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;

class ChatFaq extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['keywords' => 'array', 'is_active' => 'boolean'];
    }
}
