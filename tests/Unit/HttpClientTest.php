<?php

declare(strict_types=1);

use Quickpay\Config;
use Quickpay\CurlTransport;
use Quickpay\Exceptions\ApiException;
use Quickpay\Exceptions\AuthException;
use Quickpay\Exceptions\NotFoundException;
use Quickpay\Exceptions\RateLimitException;
use Quickpay\Exceptions\ValidationException;
use Quickpay\HttpClient;

function makeTransport(int $status, mixed $body = [], array $headers = []): CurlTransport
{
    $bodyStr = is_array($body) ? json_encode($body) : $body;
    return new class($status, $bodyStr, $headers) implements CurlTransport {
        public function __construct(
            private int $status,
            private string $body,
            private array $headers,
        ) {}

        public function send(string $method, string $url, string $body, array $headers): array
        {
            return ['body' => $this->body, 'status' => $this->status, 'headers' => $this->headers];
        }
    };
}

function makeClient(CurlTransport $transport): HttpClient
{
    return new HttpClient(new Config('qpk_test_abc'), $transport);
}

it('returns decoded body on 200', function () {
    $transport = makeTransport(200, ['uuid' => 'abc', 'status' => 'pending']);
    $result    = makeClient($transport)->request('GET', '/payments/abc');

    expect($result)->toHaveKey('uuid', 'abc');
});

it('throws AuthException on 401', function () {
    $transport = makeTransport(401, ['message' => 'Unauthorized', 'error_code' => 'invalid_api_key']);

    expect(fn() => makeClient($transport)->request('GET', '/payments/x'))
        ->toThrow(AuthException::class);
});

it('throws ValidationException with errors on 422', function () {
    $transport = makeTransport(422, [
        'message'    => 'Validation failed',
        'error_code' => 'validation_error',
        'errors'     => ['amount' => ['The amount field is required.']],
    ]);

    try {
        makeClient($transport)->request('POST', '/payments', []);
        expect(false)->toBeTrue('should have thrown');
    } catch (ValidationException $e) {
        expect($e->errors)->toHaveKey('amount')
            ->and($e->httpStatus)->toBe(422);
    }
});

it('throws RateLimitException with retryAfter on 429', function () {
    $transport = makeTransport(429, ['message' => 'Too Many Requests', 'error_code' => 'rate_limited'], ['retry-after' => '30']);

    try {
        makeClient($transport)->request('GET', '/payments');
        expect(false)->toBeTrue('should have thrown');
    } catch (RateLimitException $e) {
        expect($e->retryAfter())->toBe(30);
    }
});

it('throws NotFoundException on 404', function () {
    $transport = makeTransport(404, ['message' => 'Not found', 'error_code' => 'not_found']);

    expect(fn() => makeClient($transport)->request('GET', '/payments/nope'))
        ->toThrow(NotFoundException::class);
});

it('retries 5xx twice then throws ApiException', function () {
    $calls     = 0;
    $transport = new class($calls) implements CurlTransport {
        public function __construct(private int &$calls) {}

        public function send(string $method, string $url, string $body, array $headers): array
        {
            $this->calls++;
            return ['body' => json_encode(['message' => 'Server Error', 'error_code' => 'server_error']), 'status' => 503, 'headers' => []];
        }
    };

    try {
        (new HttpClient(new Config('qpk_test_abc'), $transport))->request('GET', '/payments');
        expect(false)->toBeTrue('should have thrown');
    } catch (ApiException $e) {
        expect($calls)->toBe(3); // 1 attempt + 2 retries
    }
});

it('strips idempotency key from body and sets as header', function () {
    $sentHeaders = [];
    $sentBody    = '';

    $transport = new class($sentHeaders, $sentBody) implements CurlTransport {
        public function __construct(private array &$headers, private string &$body) {}

        public function send(string $method, string $url, string $body, array $headers): array
        {
            $this->headers = $headers;
            $this->body    = $body;
            return ['body' => json_encode(['uuid' => 'x', 'status' => 'pending', 'amount' => 10, 'currency' => 'GEL', 'description' => '', 'gateway' => '', 'payment_url' => '', 'created_at' => '2026-01-01T00:00:00Z']), 'status' => 200, 'headers' => []];
        }
    };

    $client = new \Quickpay\QuickpayClient('qpk_test_abc', $transport);
    $client->payments->create([
        'amount'          => 10,
        'idempotency_key' => 'idem-123',
    ]);

    expect($sentHeaders)->toHaveKey('Idempotency-Key', 'idem-123');

    $decoded = json_decode($sentBody, true);
    expect($decoded)->not->toHaveKey('idempotency_key');
});
