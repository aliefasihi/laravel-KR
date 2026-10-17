<?php

/**
 * Khaneh Romantic — Fired when a category's preserved path is edited
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CategoryUrlChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $categoryId,
        public readonly string $oldPath,
        public readonly string $newPath,
    ) {}
}
