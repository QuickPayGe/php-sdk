<?php

declare(strict_types=1);

use Quickpay\CurlTransport;
use Quickpay\DTO\Paginator;
use Quickpay\DTO\Payment;
use Quickpay\QuickpayClient;

function makeClientWithResponse(array $response, int $status = 200): QuickpayClient
{
    $transport = new class($response, $status) implements CurlTransport {
        public function __construct(private array $response, private int $status) {}

        public function send(string $method, string $url, string $body, array $headers): array
        {
            return ['body' => json_encode($this->response), 'status' => $this->status, 'headers' => []];
        }
    };

    return new QuickpayClient('qpk_test_abc', $transport);
}

function samplePaymentArray(array $overrides = []): array
{
    return array_merge([
        'uuid'        => 'pay-001',
        'status'      => 'pending',
        'amount'      => '50.00',
        'currency'    => 'GEL',
        'description' => 'Test payment',
        'gateway'     => 'bog_card',
        'payment_url' => 'https://qpy.ge/pay/pay-001',
        'created_at'  => '2026-06-01T12:00:00Z',
    ], $overrides);
}

it('create returns Payment DTO with all fields mapped', function () {
    $client  = makeClientWithResponse(samplePaymentArray(['status' => 'pending']));
    $payment = $client->payments->create(['amount' => 50, 'currency' => 'GEL', 'description' => 'Test payment']);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->uuid)->toBe('pay-001')
        ->and($payment->amount)->toBe(50.0)
        ->and($payment->status)->toBe('pending')
        ->and($payment->paymentUrl)->toBe('https://qpy.ge/pay/pay-001')
        ->and($payment->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
});

it('get returns correct Payment DTO', function () {
    $client  = makeClientWithResponse(samplePaymentArray(['uuid' => 'pay-xyz', 'status' => 'paid']));
    $payment = $client->payments->get('pay-xyz');

    expect($payment->uuid)->toBe('pay-xyz')
        ->and($payment->status)->toBe('paid');
});

it('list returns Paginator with items as Payment array', function () {
    $client = makeClientWithResponse([
        'data'         => [samplePaymentArray(), samplePaymentArray(['uuid' => 'pay-002'])],
        'total'        => 2,
        'per_page'     => 15,
        'current_page' => 1,
        'last_page'    => 1,
    ]);

    $paginator = $client->payments->list();

    expect($paginator)->toBeInstanceOf(Paginator::class)
        ->and($paginator->items)->toHaveCount(2)
        ->and($paginator->items[0])->toBeInstanceOf(Payment::class)
        ->and($paginator->total)->toBe(2)
        ->and($paginator->hasMore)->toBeFalse();
});

it('refund posts to correct path', function () {
    $calledUrl = '';
    $transport = new class($calledUrl) implements CurlTransport {
        public function __construct(private string &$calledUrl) {}

        public function send(string $method, string $url, string $body, array $headers): array
        {
            $this->calledUrl = $url;
            return ['body' => json_encode(['uuid' => 'ref-1', 'amount' => 25.0, 'status' => 'refunded', 'created_at' => '2026-06-01T13:00:00Z']), 'status' => 200, 'headers' => []];
        }
    };

    $client = new QuickpayClient('qpk_test_abc', $transport);
    $result = $client->payments->refund('pay-001', 25.0);

    expect($calledUrl)->toContain('/payments/pay-001/refund')
        ->and($result->amount)->toBe(25.0)
        ->and($result->status)->toBe('refunded');
});
