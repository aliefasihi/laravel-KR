<?php

/**
 * Khaneh Romantic — Orders
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [
    // order numbers: offset + id → 100001, 100002 … (looks established, never collides with WooCommerce ids)
    'number_offset' => (int) env('ORDER_NUMBER_OFFSET', 100000),

    // unpaid online orders give their stock back after this many minutes (orders:cancel-unpaid)
    'hold_minutes' => (int) env('ORDER_HOLD_MINUTES', 30),
];
