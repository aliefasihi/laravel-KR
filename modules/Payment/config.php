<?php

/**
 * Khaneh Romantic — Payment defaults from .env (the admin settings page overrides them)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [
    'zarinpal_merchant' => env('ZARINPAL_MERCHANT_ID'),
    'zarinpal_sandbox' => (bool) env('ZARINPAL_SANDBOX', false),
];
