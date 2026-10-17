<?php

/**
 * Khaneh Romantic — Fired when a product's public path changes (brand slug or SKU edit)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ProductUrlChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $productId,
        public readonly string $oldPath,
        public readonly string $newPath,
    ) {}
}
