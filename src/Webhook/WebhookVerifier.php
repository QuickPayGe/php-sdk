<?php

declare(strict_types=1);

namespace Quickpay\Webhook;

use Quickpay\DTO\Payment;
use Quickpay\Exceptions\QuickpayException;

final class WebhookVerifier
{
    private const MAX_SKEW_SECONDS = 300;

    public function verify(string $secret, string $rawPayload, string $signatureHeader): WebhookEvent
    {
        [$timestamp, $signature] = $this->parseHeader($signatureHeader);

        $expected = hash_hmac('sha256', "$timestamp.$rawPayload", $secret);

        if (!hash_equals($expected, $signature)) {
            throw new QuickpayException('Invalid webhook signature', 'invalid_signature', 400);
        }

        $skew = abs(time() - $timestamp);
        if ($skew > self::MAX_SKEW_SECONDS) {
            throw new QuickpayException('Webhook timestamp too old', 'timestamp_expired', 400);
        }

        return $this->buildEvent($rawPayload);
    }

    public function verifyOrNull(string $secret, string $rawPayload, string $signatureHeader): ?WebhookEvent
    {
        try {
            return $this->verify($secret, $rawPayload, $signatureHeader);
        } catch (QuickpayException) {
            return null;
        }
    }

    /** @return array{int, string} */
    private function parseHeader(string $header): array
    {
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = explode('=', trim($part), 2) + ['', ''];
            $parts[$key] = $value;
        }

        if (!isset($parts['t']) || !isset($parts['v1'])) {
            throw new QuickpayException('Malformed signature header', 'invalid_signature', 400);
        }

        $timestamp = filter_var($parts['t'], FILTER_VALIDATE_INT);
        if ($timestamp === false) {
            throw new QuickpayException('Malformed signature header', 'invalid_signature', 400);
        }

        return [(int) $timestamp, $parts['v1']];
    }

    private function buildEvent(string $rawPayload): WebhookEvent
    {
        try {
            $data = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new QuickpayException('Invalid webhook JSON payload', 'invalid_payload', 400, $e);
        }

        $payment = isset($data['payment']) ? Payment::fromArray($data['payment']) : null;

        return new WebhookEvent(
            type:       $data['type'] ?? 'unknown',
            data:       $data,
            payment:    $payment,
            occurredAt: new \DateTimeImmutable($data['occurred_at'] ?? 'now'),
        );
    }
}
