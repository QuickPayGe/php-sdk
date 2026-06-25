<?php

declare(strict_types=1);

namespace Quickpay\Webhook;

use Quickpay\DTO\Payment;

final readonly class WebhookEvent
{
    public function __construct(
        public string $type,
        public array $data,
        public ?Payment $payment,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
