<?php

declare(strict_types=1);

namespace Quickpay\DTO;

final readonly class Paginator
{
    public bool $hasMore;

    public function __construct(
        public array $items,
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
    ) {
        $this->hasMore = $currentPage < $lastPage;
    }

    public static function fromArray(array $data, array $items): self
    {
        return new self(
            items:       $items,
            total:       (int) ($data['total'] ?? count($items)),
            perPage:     (int) ($data['per_page'] ?? count($items)),
            currentPage: (int) ($data['current_page'] ?? 1),
            lastPage:    (int) ($data['last_page'] ?? 1),
        );
    }
}
