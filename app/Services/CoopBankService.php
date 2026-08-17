<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CoopBankService
{
    private const TOKEN_CACHE_KEY = 'coop.access_token';

    public function initiateStk(array $payload): array
    {
        $url = (string) config('services.coop.stk_url');

        Log::info('Co-op STK initiate request', [
            'url' => $url,
            'message_reference' => $payload['MessageReference'] ?? null,
            'mobile' => $payload['MobileNumber'] ?? null,
            'amount' => $payload['Amount'] ?? null,
        ]);

        $response = $this->authenticatedClient()->post($url, $payload);
        $body = $this->decodeResponse($response->body());

        Log::info('Co-op STK initiate response', [
            'status' => $response->status(),
            'body' => $body,
        ]);

        return $body;
    }

    public function transactionStatus(string $messageReference): array
    {
        $url = (string) config('services.coop.status_url');

        Log::info('Co-op STK status request', [
            'url' => $url,
            'message_reference' => $messageReference,
        ]);

        $response = $this->authenticatedClient()->post($url, [
            'MessageReference' => $messageReference,
        ]);
        $body = $this->decodeResponse($response->body());

        Log::info('Co-op STK status response', [
            'status' => $response->status(),
            'body' => $body,
        ]);

        return $body;
    }

    public function wasStkAccepted(array $payload): bool
    {
        $code = $this->findValue($payload, [
            'MessageCode',
            'messageCode',
            'ResultCode',
            'resultCode',
            'StatusCode',
            'statusCode',
        ]);

        if ($code === null) {
            $description = strtolower((string) $this->findValue($payload, [
                'MessageDescription',
                'messageDescription',
                'ResultDesc',
                'Description',
            ]));

            return $description !== '' && str_contains($description, 'success');
        }

        $normalized = strtoupper(trim((string) $code));

        return in_array($normalized, ['0', '00', '200', '201', 'SUCCESS', 'OK'], true);
    }

    /**
     * @return 'successful'|'failed'|'pending'
     */
    public function interpretOutcome(array $payload): string
    {
        $status = strtoupper(trim((string) $this->findValue($payload, [
            'Status',
            'status',
            'TransactionStatus',
            'transactionStatus',
            'Result',
            'result',
        ])));

        if (in_array($status, ['SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'PAID', 'COMPLETE', 'PROCESSED'], true)) {
            return 'successful';
        }

        if (in_array($status, ['FAILED', 'FAILURE', 'CANCELLED', 'CANCELED', 'TIMEOUT', 'EXPIRED', 'DECLINED', 'REJECTED'], true)) {
            return 'failed';
        }

        $code = $this->findValue($payload, [
            'ResultCode',
            'resultCode',
            'MessageCode',
            'messageCode',
        ]);

        if ($code !== null) {
            $normalized = strtoupper(trim((string) $code));
            if (in_array($normalized, ['0', '00', '200', '201', 'SUCCESS'], true)) {
                $description = strtolower((string) $this->findValue($payload, [
                    'ResultDesc',
                    'MessageDescription',
                    'Description',
                ]));

                if ($description !== '' && (
                    str_contains($description, 'fail')
                    || str_contains($description, 'cancel')
                    || str_contains($description, 'timeout')
                    || str_contains($description, 'expired')
                )) {
                    return 'failed';
                }

                if ($status === 'PENDING' || $status === 'PROCESSING' || $status === 'QUEUED') {
                    return 'pending';
                }

                if ($this->findValue($payload, ['TransactionId', 'TransactionID', 'transactionId', 'ReceiptNumber']) !== null) {
                    return 'successful';
                }
            }

            if (! in_array($normalized, ['0', '00', '200', '201', 'SUCCESS', 'PENDING', '1'], true)) {
                return 'failed';
            }
        }

        $description = strtolower((string) $this->findValue($payload, [
            'MessageDescription',
            'ResultDesc',
            'Description',
            'Message',
        ]));

        if (str_contains($description, 'fail') || str_contains($description, 'cancel') || str_contains($description, 'timeout')) {
            return 'failed';
        }

        return 'pending';
    }

    public function extractMessageReference(array $payload): ?string
    {
        $value = $this->findValue($payload, [
            'MessageReference',
            'messageReference',
            'message_reference',
        ]);

        return $value !== null ? trim((string) $value) : null;
    }

    public function extractTransactionId(array $payload): ?string
    {
        $value = $this->findValue($payload, [
            'TransactionId',
            'TransactionID',
            'transactionId',
            'transaction_id',
            'ReceiptNumber',
            'receiptNumber',
        ]);

        return $value !== null ? trim((string) $value) : null;
    }

    public function extractPhoneNumber(array $payload): ?string
    {
        $value = $this->findValue($payload, [
            'MobileNumber',
            'mobileNumber',
            'MSISDN',
            'msisdn',
            'PhoneNumber',
            'phoneNumber',
        ]);

        return $value !== null ? trim((string) $value) : null;
    }

    public function extractAmount(array $payload): ?float
    {
        $value = $this->findValue($payload, ['Amount', 'amount', 'TransAmount']);

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    public function callbackUrl(): string
    {
        $url = (string) (config('services.coop.callback_url') ?: rtrim((string) config('app.url'), '/').'/api/payments/coop/stk-callback');
        $token = (string) config('services.coop.callback_token');

        if ($token !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?').'token='.urlencode($token);
        }

        return $url;
    }

    /**
     * @return array<string, mixed>
     */
    public function findValue(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                return $payload[$key];
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $found = $this->findValue($value, $keys);
                if ($found !== null && $found !== '') {
                    return $found;
                }
            }
        }

        return null;
    }

    private function authenticatedClient(): PendingRequest
    {
        return Http::timeout((int) config('services.coop.timeout', 30))
            ->acceptJson()
            ->asJson()
            ->withToken($this->accessToken());
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $key = (string) config('services.coop.consumer_key');
        $secret = (string) config('services.coop.consumer_secret');

        if ($key === '' || $secret === '') {
            throw new RuntimeException('Co-op Bank consumer key/secret are not configured.');
        }

        $response = Http::timeout((int) config('services.coop.timeout', 30))
            ->asForm()
            ->withBasicAuth($key, $secret)
            ->post((string) config('services.coop.token_url'), [
                'grant_type' => 'client_credentials',
            ]);

        $body = $this->decodeResponse($response->body());
        $token = $body['access_token'] ?? $body['accessToken'] ?? null;

        if (! $response->successful() || ! is_string($token) || $token === '') {
            Log::error('Co-op token request failed', [
                'status' => $response->status(),
                'body' => $body,
            ]);

            throw new RuntimeException('Unable to obtain Co-op Bank access token.');
        }

        $ttl = max(60, (int) ($body['expires_in'] ?? 3600) - 60);
        Cache::put(self::TOKEN_CACHE_KEY, $token, $ttl);

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(string $body): array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : ['_raw' => $body];
    }
}
