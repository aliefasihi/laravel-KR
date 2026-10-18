<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Support;

use RuntimeException;

/** A checkout problem the shopper can fix (message is shown as is). */
class OrderException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'order')
    {
        parent::__construct($message);
    }
}
