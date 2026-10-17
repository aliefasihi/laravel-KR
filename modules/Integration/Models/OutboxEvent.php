<?php

/**
 * Khaneh Romantic — Outbox event
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Record inside the same DB transaction as the business change:
 *   DB::transaction(fn () => [$order->markPaid(), OutboxEvent::record('order.paid', [...])]);
 * A queued dispatcher (next step) delivers pending events with HMAC-signed webhooks.
 */

namespace Modules\Integration\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'available_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public static function record(string $type, array $payload): self
    {
        return static::query()->create(['type' => $type, 'payload' => $payload, 'available_at' => now()]);
    }
}
