<?php

declare(strict_types=1);

namespace Quickpay\Resources;

use Quickpay\DTO\CheckoutLink;
use Quickpay\DTO\Paginator;
use Quickpay\HttpClient;

final class CheckoutLinks
{
    public function __construct(private readonly HttpClient $http) {}

    public function create(array $data): CheckoutLink
    {
        return CheckoutLink::fromArray($this->http->request('POST', '/checkout-links', $data));
    }

    public function get(string $uuid): CheckoutLink
    {
        return CheckoutLink::fromArray($this->http->request('GET', "/checkout-links/$uuid"));
    }

    public function list(array $filters = []): Paginator
    {
        $response = $this->http->request('GET', '/checkout-links', $filters);
        $items    = array_map(fn(array $row) => CheckoutLink::fromArray($row), $response['data'] ?? []);

        return Paginator::fromArray($response, $items);
    }
}
