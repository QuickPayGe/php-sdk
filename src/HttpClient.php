<?php

declare(strict_types=1);

namespace Quickpay;

use Quickpay\Exceptions\ApiException;
use Quickpay\Exceptions\AuthException;
use Quickpay\Exceptions\NotFoundException;
use Quickpay\Exceptions\RateLimitException;
use Quickpay\Exceptions\ValidationException;

final class HttpClient
{
    private CurlTransport $transport;

    public function __construct(
        private readonly Config $config,
        ?CurlTransport $transport = null,
    ) {
        $this->transport = $transport ?? new DefaultCurlTransport();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string> $extraHeaders
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $data = [], array $extraHeaders = []): array
    {
        $url  = rtrim($this->config->baseUrl, '/') . '/' . ltrim($path, '/');
        $body = ($method !== 'GET' && $data !== []) ? json_encode($data, JSON_THROW_ON_ERROR) : '';

        $headers = array_merge($this->defaultHeaders(), $extraHeaders);

        if ($method === 'GET' && $data !== []) {
            $url .= '?' . http_build_query($data);
            $body = '';
        }

        $maxAttempts = 3;
        $delays      = [2, 4]; // seconds between retries

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = $this->transport->send($method, $url, $body, $headers);
            $status   = $response['status'];

            if ($status >= 200 && $status < 300) {
                return $response['body'] !== '' ? json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR) : [];
            }

            // Retry on 5xx (up to 2 retries, then throw on 3rd failure)
            if ($status >= 500 && $attempt < $maxAttempts) {
                sleep($delays[$attempt - 1]);
                continue;
            }

            $this->throwForStatus($status, $response);
        }

        // Should be unreachable; last 5xx attempt throws inside throwForStatus
        throw new ApiException('Unexpected error after retries', 'api_error', 0);
    }

    /** @return array<string, string> */
    private function defaultHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->config->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'quickpay-php/1.0',
        ];

        if ($this->config->siteDomain !== '') {
            $headers['X-Site-Domain'] = $this->config->siteDomain;
        }

        return $headers;
    }

    /** @param array{body: string, status: int, headers: array<string, string>} $response */
    private function throwForStatus(int $status, array $response): never
    {
        $payload   = [];
        $errorCode = 'api_error';

        if ($response['body'] !== '') {
            try {
                $payload   = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
                $errorCode = $payload['error_code'] ?? $errorCode;
            } catch (\JsonException) {
                // Use defaults
            }
        }

        $message = $payload['message'] ?? "HTTP $status";

        match (true) {
            $status === 401 || $status === 403 => throw new AuthException($message, $errorCode, $status),
            $status === 404                    => throw new NotFoundException($message, $errorCode, $status),
            $status === 422                    => throw new ValidationException($message, $errorCode, $status, $payload['errors'] ?? []),
            $status === 429                    => throw new RateLimitException($message, $errorCode, $status, (int) ($response['headers']['retry-after'] ?? 60)),
            default                            => throw new ApiException($message, $errorCode, $status),
        };
    }
}
