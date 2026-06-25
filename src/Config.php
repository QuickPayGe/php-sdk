<?php

declare(strict_types=1);

namespace Quickpay;

final class Config
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl = 'https://api.quickpay.ge/v1',
        public readonly int $timeout = 30,
        public readonly string $siteDomain = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            apiKey:     $data['api_key'],
            baseUrl:    $data['base_url'] ?? 'https://api.quickpay.ge/v1',
            timeout:    $data['timeout'] ?? 30,
            siteDomain: $data['site_domain'] ?? '',
        );
    }
}
