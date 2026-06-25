<?php

declare(strict_types=1);

namespace Quickpay\DTO;

final readonly class RefundResult
{
    public function __construct(
        public string $uuid,
        public float $amount,
        public string $status,
        public \DateTimeImmutable $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uuid:      $data['uuid'],
            amount:    (float) $data['amount'],
            status:    $data['status'],
            createdAt: new \DateTimeImmutable($data['created_at']),
        );
    }
}
