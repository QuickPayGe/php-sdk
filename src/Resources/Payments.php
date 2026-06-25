<?php

declare(strict_types=1);

namespace Quickpay\Resources;

use Quickpay\DTO\Paginator;
use Quickpay\DTO\Payment;
use Quickpay\DTO\RefundResult;
use Quickpay\HttpClient;

final class Payments
{
    public function __construct(private readonly HttpClient $http) {}

    public function create(array $data): Payment
    {
        $headers = [];
        if (isset($data['idempotency_key'])) {
            $headers['Idempotency-Key'] = $data['idempotency_key'];
            unset($data['idempotency_key']);
        }

        $response = $this->http->request('POST', '/payments', $data, $headers);

        return Payment::fromArray($response);
    }

    public function get(string $uuid): Payment
    {
        return Payment::fromArray($this->http->request('GET', "/payments/$uuid"));
    }

    public function list(array $filters = []): Paginator
    {
        $allowed  = ['status', 'gateway', 'date_from', 'date_to', 'page', 'per_page'];
        $filtered = array_intersect_key($filters, array_flip($allowed));

        $response = $this->http->request('GET', '/payments', $filtered);
        $items    = array_map(fn(array $row) => Payment::fromArray($row), $response['data'] ?? []);

        return Paginator::fromArray($response, $items);
    }

    public function refund(string $uuid, float $amount, string $reason = ''): RefundResult
    {
        $body = ['amount' => $amount];
        if ($reason !== '') {
            $body['reason'] = $reason;
        }

        return RefundResult::fromArray($this->http->request('POST', "/payments/$uuid/refund", $body));
    }
}
