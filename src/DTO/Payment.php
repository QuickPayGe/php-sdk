<?php

declare(strict_types=1);

namespace Quickpay\DTO;

final readonly class Payment
{
    public function __construct(
        public string $uuid,
        public string $status,
        public float $amount,
        public string $currency,
        public string $description,
        public string $gateway,
        public string $paymentUrl,
        public ?string $merchantOrderId,
        public array $customer,
        public array $metadata,
        public array $lineItems,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $paidAt,
        public ?\DateTimeImmutable $expiresAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uuid:            $data['uuid'],
            status:          $data['status'],
            amount:          (float) $data['amount'],
            currency:        $data['currency'] ?? 'GEL',
            description:     $data['description'] ?? '',
            gateway:         $data['gateway'] ?? '',
            paymentUrl:      $data['payment_url'] ?? '',
            merchantOrderId: $data['merchant_order_id'] ?? null,
            customer:        $data['customer'] ?? [],
            metadata:        $data['metadata'] ?? [],
            lineItems:       $data['line_items'] ?? [],
            createdAt:       new \DateTimeImmutable($data['created_at']),
            paidAt:          isset($data['paid_at']) ? new \DateTimeImmutable($data['paid_at']) : null,
            expiresAt:       isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null,
        );
    }
}
