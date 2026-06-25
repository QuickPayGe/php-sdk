<?php

declare(strict_types=1);

namespace Quickpay\Resources;

use Quickpay\DTO\Paginator;
use Quickpay\DTO\Product;
use Quickpay\HttpClient;

final class Products
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(array $filters = []): Paginator
    {
        $response = $this->http->request('GET', '/products', $filters);
        $items    = array_map(fn(array $row) => Product::fromArray($row), $response['data'] ?? []);

        return Paginator::fromArray($response, $items);
    }

    public function get(string $uuid): Product
    {
        return Product::fromArray($this->http->request('GET', "/products/$uuid"));
    }

    public function create(array $data): Product
    {
        return Product::fromArray($this->http->request('POST', '/products', $data));
    }

    public function update(string $uuid, array $data): Product
    {
        return Product::fromArray($this->http->request('PUT', "/products/$uuid", $data));
    }
}
