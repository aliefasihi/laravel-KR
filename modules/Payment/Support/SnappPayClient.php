<?php

/**
 * Khaneh Romantic — SnappPay («اسنپ‌پی») buy-now-pay-later gateway, API v2.1
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   oauth/token (password grant, Basic client id/secret) → eligible?amount → payment/v1/token
 *   (shopper picks installments on SnappPay) → POST back to returnURL {transactionId, state, amount}
 *   → verify(paymentToken) → settle(paymentToken). cancel() / revert() for refunds from the admin.
 * The base URL and the four credentials come from SnappPay with the merchant contract.
 * Amounts in Rials; mobile as +989…; a fresh access token per call (SnappPay doesn't want them cached).
 */

namespace Modules\Payment\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SnappPayClient
{
    public function __construct(
        private string $baseUrl,
        private string $clientId,
        private string $clientSecret,
        private string $username,
        private string $password,
    ) {}

    /** @return array{eligible: bool, title: ?string, description: ?string} */
    public function eligible(int $amount): array
    {
        $data = $this->data($this->api()->get('/api/online/offer/v1/eligible', ['amount' => $amount]));

        return [
            'eligible' => (bool) ($data['eligible'] ?? false),
            'title' => $data['title_message'] ?? $data['title'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }

    /** @return array{token: string, url: string} */
    public function token(array $payload): array
    {
        $data = $this->data($this->api()->asJson()->post('/api/online/payment/v1/token', $payload));
        if (empty($data['paymentToken']) || empty($data['paymentPageUrl'])) {
            throw new RuntimeException('SnappPay: no paymentToken / paymentPageUrl in the answer');
        }

        return ['token' => (string) $data['paymentToken'], 'url' => (string) $data['paymentPageUrl']];
    }

    public function verify(string $paymentToken): array
    {
        return $this->data($this->api()->asJson()->post('/api/online/payment/v1/verify', ['paymentToken' => $paymentToken]));
    }

    public function settle(string $paymentToken): array
    {
        return $this->data($this->api()->asJson()->post('/api/online/payment/v1/settle', ['paymentToken' => $paymentToken]));
    }

    public function cancel(string $paymentToken): array
    {
        return $this->data($this->api()->asJson()->post('/api/online/payment/v1/cancel', ['paymentToken' => $paymentToken]));
    }

    /** @return string e.g. PENDING · VERIFY · SETTLE · CANCEL · REVERT */
    public function status(string $paymentToken): string
    {
        $data = $this->data($this->api()->get('/api/online/payment/v1/status', ['paymentToken' => $paymentToken]));

        return strtoupper((string) ($data['status'] ?? ''));
    }

    /** +989121234567 */
    public static function mobile(?string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', strtr((string) $mobile, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']));

        return match (true) {
            (bool) preg_match('/^09\d{9}$/', $digits) => '+98'.substr($digits, 1),
            (bool) preg_match('/^989\d{9}$/', $digits) => '+'.$digits,
            (bool) preg_match('/^9\d{9}$/', $digits) => '+98'.$digits,
            default => null,
        };
    }

    private function http(): PendingRequest
    {
        if ($this->baseUrl === '') {
            throw new RuntimeException('SnappPay: base URL is not set');
        }

        return Http::baseUrl(rtrim($this->baseUrl, '/'))->acceptJson()->timeout(20)->connectTimeout(8);
    }

    private function api(): PendingRequest
    {
        $auth = $this->http()->withBasicAuth($this->clientId, $this->clientSecret)->asForm()->post('/api/online/v1/oauth/token', [
            'grant_type' => 'password',
            'scope' => 'online-merchant',
            'username' => $this->username,
            'password' => $this->password,
        ]);
        $token = $auth->successful() ? $auth->json('access_token') : null;
        if (! $token) {
            throw new RuntimeException('SnappPay: login failed (HTTP '.$auth->status().')');
        }

        return $this->http()->withToken((string) $token);
    }

    /** The answer's `response` / `data` part; throws when SnappPay says it failed. */
    private function data(Response $response): array
    {
        $json = (array) $response->json();
        if (! $response->successful() || ($json['successful'] ?? true) === false) {
            $message = $json['errorData']['message'] ?? $json['message'] ?? mb_substr($response->body(), 0, 300);
            throw new RuntimeException('SnappPay: '.$message.' (HTTP '.$response->status().')');
        }

        return (array) ($json['response'] ?? $json['data'] ?? $json);
    }
}
