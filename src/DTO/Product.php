<?php

declare(strict_types=1);

namespace Quickpay\DTO;

final readonly class Product
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?float $amount,
        public string $currency,
        public ?string $description,
        public bool $isSubscription,
        public \DateTimeImmutable $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uuid:           $data['uuid'],
            name:           $data['name'],
            amount:         isset($data['amount']) ? (float) $data['amount'] : null,
            currency:       $data['currency'] ?? 'GEL',
            description:    $data['description'] ?? null,
            isSubscription: (bool) ($data['is_subscription'] ?? false),
            createdAt:      new \DateTimeImmutable($data['created_at']),
        );
    }
}
