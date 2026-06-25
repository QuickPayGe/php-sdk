<?php

declare(strict_types=1);

use Quickpay\DTO\Payment;
use Quickpay\DTO\Paginator;

it('maps all Payment fields from array', function () {
    $data = [
        'uuid'              => 'abc-123',
        'status'            => 'paid',
        'amount'            => '99.99',
        'currency'          => 'GEL',
        'description'       => 'Test order',
        'gateway'           => 'bog_card',
        'payment_url'       => 'https://qpy.ge/pay/abc-123',
        'merchant_order_id' => 'order-1',
        'customer'          => ['name' => 'John'],
        'metadata'          => ['source' => 'web'],
        'line_items'        => [],
        'created_at'        => '2026-01-01T10:00:00Z',
        'paid_at'           => '2026-01-01T10:05:00Z',
        'expires_at'        => null,
    ];

    $payment = Payment::fromArray($data);

    expect($payment->uuid)->toBe('abc-123')
        ->and($payment->status)->toBe('paid')
        ->and($payment->amount)->toBe(99.99)
        ->and($payment->currency)->toBe('GEL')
        ->and($payment->gateway)->toBe('bog_card')
        ->and($payment->paymentUrl)->toBe('https://qpy.ge/pay/abc-123')
        ->and($payment->merchantOrderId)->toBe('order-1')
        ->and($payment->customer)->toBe(['name' => 'John'])
        ->and($payment->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($payment->paidAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($payment->expiresAt)->toBeNull();
});

it('leaves paidAt null when absent', function () {
    $payment = Payment::fromArray([
        'uuid'       => 'x',
        'status'     => 'pending',
        'amount'     => 10,
        'currency'   => 'GEL',
        'description'=> '',
        'gateway'    => '',
        'payment_url'=> '',
        'created_at' => '2026-01-01T00:00:00Z',
    ]);

    expect($payment->paidAt)->toBeNull();
});

it('createdAt is DateTimeImmutable', function () {
    $payment = Payment::fromArray([
        'uuid'       => 'x',
        'status'     => 'pending',
        'amount'     => 5,
        'currency'   => 'GEL',
        'description'=> '',
        'gateway'    => '',
        'payment_url'=> '',
        'created_at' => '2026-06-15T14:30:00Z',
    ]);

    expect($payment->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($payment->createdAt->format('Y'))->toBe('2026');
});

it('Paginator hasMore is true when currentPage < lastPage', function () {
    $paginator = new Paginator(items: [], total: 30, perPage: 10, currentPage: 1, lastPage: 3);

    expect($paginator->hasMore)->toBeTrue();
});

it('Paginator hasMore is false on last page', function () {
    $paginator = new Paginator(items: [], total: 10, perPage: 10, currentPage: 1, lastPage: 1);

    expect($paginator->hasMore)->toBeFalse();
});
