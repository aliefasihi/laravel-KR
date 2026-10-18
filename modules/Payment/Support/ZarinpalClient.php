<?php

/**
 * Khaneh Romantic — Zarinpal payment gateway (REST v4)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   request → authority → shopper pays on StartPay/{authority} → callback ?Authority=…&Status=OK → verify
 * Amounts are in Rials (Zarinpal's default currency).
 */

namespace Modules\Payment\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZarinpalClient
{
    public function __construct(private string $merchantId, private bool $sandbox = false) {}

    private function base(): string
    {
        return $this->sandbox ? 'https://sandbox.zarinpal.com/pg' : 'https://payment.zarinpal.com/pg';
    }

    /** @return string authority */
    public function request(int $amount, string $callbackUrl, string $description, ?string $mobile = null, ?string $orderId = null): string
    {
        $response = Http::acceptJson()->timeout(15)->retry(2, 500, throw: false)->post($this->base().'/v4/payment/request.json', array_filter([
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'currency' => 'IRR',
            'callback_url' => $callbackUrl,
            'description' => $description,
            'metadata' => array_filter(['mobile' => $mobile, 'order_id' => $orderId]),
        ]));

        $code = (int) $response->json('data.code');
        if ($code !== 100 || ! $response->json('data.authority')) {
            throw new RuntimeException('Zarinpal request failed: '.$this->error($response->json()));
        }

        return (string) $response->json('data.authority');
    }

    public function payUrl(string $authority): string
    {
        return $this->base().'/StartPay/'.$authority;
    }

    /**
     * @return array{ok: bool, already: bool, ref_id: ?string, card_pan: ?string, raw: array, error: ?string}
     */
    public function verify(int $amount, string $authority): array
    {
        $response = Http::acceptJson()->timeout(20)->retry(2, 500, throw: false)->post($this->base().'/v4/payment/verify.json', [
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'authority' => $authority,
        ]);
        $code = (int) $response->json('data.code');

        return [
            'ok' => in_array($code, [100, 101], true),
            'already' => $code === 101,
            'ref_id' => $response->json('data.ref_id') !== null ? (string) $response->json('data.ref_id') : null,
            'card_pan' => $response->json('data.card_pan'),
            'raw' => (array) $response->json(),
            'error' => in_array($code, [100, 101], true) ? null : $this->error($response->json()),
        ];
    }

    private function error(?array $json): string
    {
        $errors = $json['errors'] ?? [];

        return (string) (is_array($errors) ? ($errors['message'] ?? ($errors['code'] ?? json_encode($errors))) : $errors) ?: 'unknown';
    }
}
