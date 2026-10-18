<?php

/**
 * Khaneh Romantic — Models whose changes must refresh cached storefront blocks
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Core\Concerns;

use App\Core\Support\StoreCache;

trait FlushesStoreCache
{
    protected static function bootFlushesStoreCache(): void
    {
        static::saved(fn () => StoreCache::flush());
        static::deleted(fn () => StoreCache::flush());
    }
}
