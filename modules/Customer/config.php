<?php

/**
 * Khaneh Romantic — Customer accounts (OTP login)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

return [
    'otp' => [
        'length' => 5,
        'ttl' => 120,             // seconds a code stays valid
        'resend_after' => 60,     // seconds before «ارسال دوباره»
        'max_attempts' => 5,      // wrong tries per code
        'per_mobile_hour' => 5,   // codes per number per hour
        'per_ip_hour' => 20,      // codes per IP per hour
        // WebOTP (Chrome/Android fills the code by itself): the SMS template's last line must be
        // "@khanehromantic.com #%token%" — set the domain here, null turns it off
        'webotp_domain' => env('OTP_WEBOTP_DOMAIN', parse_url((string) env('APP_URL'), PHP_URL_HOST) ?: null),
    ],

    'provinces' => [
        'آذربایجان شرقی', 'آذربایجان غربی', 'اردبیل', 'اصفهان', 'البرز', 'ایلام', 'بوشهر', 'تهران',
        'چهارمحال و بختیاری', 'خراسان جنوبی', 'خراسان رضوی', 'خراسان شمالی', 'خوزستان', 'زنجان', 'سمنان',
        'سیستان و بلوچستان', 'فارس', 'قزوین', 'قم', 'کردستان', 'کرمان', 'کرمانشاه', 'کهگیلویه و بویراحمد',
        'گلستان', 'گیلان', 'لرستان', 'مازندران', 'مرکزی', 'هرمزگان', 'همدان', 'یزد',
    ],
];
