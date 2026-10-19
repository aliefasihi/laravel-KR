<?php

/**
 * Khaneh Romantic — One run of the WordPress importer
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRun extends Model
{
    public const STATUSES = ['queued' => 'در صف', 'running' => 'در حال اجرا', 'done' => 'تمام شد', 'failed' => 'خطا'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'steps' => 'array', 'counts' => 'array', 'settings' => 'array', 'log' => 'array', 'dry_run' => 'boolean',
            'started_at' => 'datetime', 'finished_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class, 'source_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }
}
