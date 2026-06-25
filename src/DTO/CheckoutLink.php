<?php

declare(strict_types=1);

namespace Quickpay\DTO;

final readonly class CheckoutLink
{
    public function __construct(
        public string $uuid,
        public string $slug,
        public string $url,
        public ?float $amount,
        public string $currency,
        public string $description,
        public ?string $productUuid,
        public bool $active,
        public \DateTimeImmutable $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uuid:        $data['uuid'],
            slug:        $data['slug'],
            url:         $data['url'],
            amount:      isset($data['amount']) ? (float) $data['amount'] : null,
            currency:    $data['currency'] ?? 'GEL',
            description: $data['description'] ?? '',
            productUuid: $data['product_uuid'] ?? null,
            active:      (bool) ($data['active'] ?? true),
            createdAt:   new \DateTimeImmutable($data['created_at']),
        );
    }
}
