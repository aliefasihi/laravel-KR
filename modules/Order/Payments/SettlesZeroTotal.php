<?php

/**
 * Khaneh Romantic — Marks a payment method used when nothing is left to pay (e.g. all from the wallet)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Payments;

interface SettlesZeroTotal extends PaymentMethod {}
