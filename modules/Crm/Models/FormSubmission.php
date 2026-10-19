<?php

/**
 * Khaneh Romantic — One filled-in form (contact, newsletter, review, comment…)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormSubmission extends Model
{
    public const STATUSES = ['new' => 'جدید', 'read' => 'خوانده‌شده', 'done' => 'رسیدگی شد', 'spam' => 'هرزنامه'];

    public const COLORS = ['new' => 'warning', 'read' => 'info', 'done' => 'success', 'spam' => 'gray'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array', 'utm' => 'array', 'handled_at' => 'datetime'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
