<?php

declare(strict_types=1);

use Quickpay\Exceptions\QuickpayException;
use Quickpay\Webhook\WebhookEvent;
use Quickpay\Webhook\WebhookVerifier;

function makeValidHeader(string $secret, string $payload, int $timestamp): string
{
    $sig = hash_hmac('sha256', "$timestamp.$payload", $secret);
    return "t=$timestamp,v1=$sig";
}

it('returns WebhookEvent on valid signature', function () {
    $secret    = 'my_secret';
    $timestamp = time();
    $payload   = json_encode([
        'type'        => 'payment.paid',
        'occurred_at' => '2026-01-01T10:00:00Z',
        'payment'     => [
            'uuid'       => 'abc',
            'status'     => 'paid',
            'amount'     => 50,
            'currency'   => 'GEL',
            'description'=> 'Test',
            'gateway'    => 'bog_card',
            'payment_url'=> 'https://qpy.ge',
            'created_at' => '2026-01-01T09:55:00Z',
        ],
    ]);

    $header = makeValidHeader($secret, $payload, $timestamp);
    $event  = (new WebhookVerifier())->verify($secret, $payload, $header);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->type)->toBe('payment.paid')
        ->and($event->payment)->not->toBeNull()
        ->and($event->payment->uuid)->toBe('abc');
});

it('throws on wrong HMAC', function () {
    $secret    = 'correct_secret';
    $timestamp = time();
    $payload   = json_encode(['type' => 'payment.paid', 'occurred_at' => '2026-01-01T00:00:00Z']);
    $header    = "t=$timestamp,v1=badhash";

    expect(fn() => (new WebhookVerifier())->verify($secret, $payload, $header))
        ->toThrow(QuickpayException::class);
});

it('throws when timestamp is older than 300 seconds', function () {
    $secret    = 'my_secret';
    $timestamp = time() - 301;
    $payload   = json_encode(['type' => 'test', 'occurred_at' => '2026-01-01T00:00:00Z']);
    $header    = makeValidHeader($secret, $payload, $timestamp);

    expect(fn() => (new WebhookVerifier())->verify($secret, $payload, $header))
        ->toThrow(QuickpayException::class, 'timestamp');
});

it('throws when t= is missing from header', function () {
    $payload = json_encode(['type' => 'test', 'occurred_at' => '2026-01-01T00:00:00Z']);

    expect(fn() => (new WebhookVerifier())->verify('secret', $payload, 'v1=somehash'))
        ->toThrow(QuickpayException::class);
});

it('verifyOrNull returns null on failure', function () {
    $result = (new WebhookVerifier())->verifyOrNull('secret', 'payload', 'bad=header');

    expect($result)->toBeNull();
});

it('verifyOrNull returns event on success', function () {
    $secret    = 'my_secret';
    $timestamp = time();
    $payload   = json_encode(['type' => 'lead.submitted', 'occurred_at' => '2026-01-01T00:00:00Z']);
    $header    = makeValidHeader($secret, $payload, $timestamp);

    $event = (new WebhookVerifier())->verifyOrNull($secret, $payload, $header);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->payment)->toBeNull();
});
