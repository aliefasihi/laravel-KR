<?php

/**
 * Khaneh Romantic — SMS defaults from .env (the admin settings page overrides them)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [
    'driver' => env('SMS_DRIVER', 'log'), // log | kavenegar | smsir
    'kavenegar_key' => env('KAVENEGAR_API_KEY'),
    'kavenegar_otp_template' => env('KAVENEGAR_OTP_TEMPLATE', 'login'),
    'kavenegar_sender' => env('KAVENEGAR_SENDER'),
    'smsir_key' => env('SMSIR_API_KEY'),
    'smsir_otp_template' => env('SMSIR_OTP_TEMPLATE'),
    'smsir_otp_parameter' => env('SMSIR_OTP_PARAMETER', 'CODE'),
    'smsir_line' => env('SMSIR_LINE'),
];
