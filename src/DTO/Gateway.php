<?php

declare(strict_types=1);

namespace Quickpay\DTO;

final readonly class Gateway
{
    public function __construct(
        public string $slug,
        public string $name,
        public bool $active,
        public bool $supportsInstallments,
        public bool $supportsSubscriptions,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            slug:                  $data['slug'],
            name:                  $data['name'],
            active:                (bool) ($data['active'] ?? true),
            supportsInstallments:  (bool) ($data['supports_installments'] ?? false),
            supportsSubscriptions: (bool) ($data['supports_subscriptions'] ?? false),
        );
    }
}
