<?php

/**
 * Khaneh Romantic — Ledger rows that can only be added, never changed or removed
 * (corrections are new, reversing rows). Wallet and points ledgers use it.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Core\Concerns;

use LogicException;

trait AppendOnly
{
    protected static function bootAppendOnly(): void
    {
        $refuse = fn () => throw new LogicException(class_basename(static::class).' rows are append-only (immutable).');
        static::updating($refuse);
        static::deleting($refuse);
    }
}
