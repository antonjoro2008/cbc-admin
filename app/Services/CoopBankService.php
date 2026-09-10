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
        $response = $this->loggedClient('STK initiate', $this->authenticatedClient())
            ->post($url, $payload);
        $body = $this->decodeResponse($response->body());

        $this->logIncoming('STK initiate', $response, $body);

        return $body;
    }

    public function transactionStatus(string $messageReference): array
    {
        $url = (string) config('services.coop.status_url');
        $response = $this->loggedClient('STK status', $this->authenticatedClient())
            ->post($url, [
                'MessageReference' => $messageReference,
            ]);
        $body = $this->decodeResponse($response->body());

        $this->logIncoming('STK status', $response, $body);

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
     * Interpret STK Transaction Status (not the initial STK Push ack).
     *
     * Co-op confirmed they will not send STK callbacks. MessageCode 0 on the
     * status API usually means the enquiry succeeded, not that the customer paid.
     *
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

        if (in_array($status, ['SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'PAID', 'COMPLETE', 'PROCESSED', 'SETTLED', 'CREDIT', 'CREDITED'], true)) {
            return 'successful';
        }

        if (in_array($status, ['FAILED', 'FAILURE', 'CANCELLED', 'CANCELED', 'TIMEOUT', 'EXPIRED', 'DECLINED', 'REJECTED', 'NOT FOUND', 'NOTFOUND'], true)) {
            return 'failed';
        }

        $description = strtolower((string) $this->findValue($payload, [
            'MessageDescription',
            'ResultDesc',
            'Description',
            'Message',
            'TransactionStatusDescription',
        ]));

        if ($description !== '') {
            if (str_contains($description, 'fail')
                || str_contains($description, 'cancel')
                || str_contains($description, 'timeout')
                || str_contains($description, 'expired')
                || str_contains($description, 'declin')
                || str_contains($description, 'not found')
                || str_contains($description, 'insufficient')
            ) {
                return 'failed';
            }

            if (
                str_contains($description, 'completed')
                || str_contains($description, 'paid')
                || str_contains($description, 'settled')
                || str_contains($description, 'credited')
                || preg_match('/\b(transaction|payment)\s+(is\s+)?success/', $description)
            ) {
                return 'successful';
            }
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
        return $this->baseClient()
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

        $url = (string) config('services.coop.token_url');
        $bodyString = 'grant_type=client_credentials';

        $response = $this->loggedClient('token', $this->baseClient())
            ->withHeaders([
                'Accept' => '*/*',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode($key.':'.$secret),
            ])
            ->withBody($bodyString, 'application/x-www-form-urlencoded')
            ->post($url);

        $body = $this->decodeResponse($response->body());
        $token = $body['access_token'] ?? $body['accessToken'] ?? null;

        if (! $response->successful() || ! is_string($token) || $token === '') {
            $this->logIncoming('token', $response, $body, error: true);

            throw new RuntimeException('Unable to obtain Co-op Bank access token.');
        }

        $this->logIncoming('token', $response, ['token' => 'obtained', 'expires_in' => $body['expires_in'] ?? null]);

        $ttl = max(60, (int) ($body['expires_in'] ?? 3600) - 60);
        Cache::put(self::TOKEN_CACHE_KEY, $token, $ttl);

        return $token;
    }

    private function baseClient(): PendingRequest
    {
        $client = Http::timeout((int) config('services.coop.timeout', 30))
            ->withOptions([
                'http_errors' => false,
                'version' => '1.1',
            ]);

        $userAgent = trim((string) config('services.coop.user_agent', 'GravityCBC-API/1.0'));
        if ($userAgent !== '') {
            $client = $client->withHeaders(['User-Agent' => $userAgent]);
        }

        return $client;
    }

    private function loggedClient(string $label, PendingRequest $pending): PendingRequest
    {
        return $pending->beforeSending(function ($request, $options) use ($label) {
            Log::info("Co-op outgoing {$label}", [
                'method' => $request->method(),
                'url' => (string) $request->url(),
                'headers' => $this->headersForLog($request->headers()),
                'body' => $request->body(),
                'http_version' => $options['version'] ?? null,
                'timeout' => $options['timeout'] ?? null,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function headersForLog(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[$name] = is_array($value) ? implode(', ', $value) : $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function logIncoming(string $label, \Illuminate\Http\Client\Response $response, array $body, bool $error = false): void
    {
        $payload = [
            'status' => $response->status(),
            'reason' => $response->reason(),
            'effective_url' => (string) $response->effectiveUri(),
            'headers' => $this->headersForLog($response->headers()),
            'body' => $body,
        ];

        if ($error) {
            Log::error("Co-op incoming {$label}", $payload);

            return;
        }

        Log::info("Co-op incoming {$label}", $payload);
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
