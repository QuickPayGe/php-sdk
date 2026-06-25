<?php

declare(strict_types=1);

namespace Quickpay\Resources;

use Quickpay\DTO\Gateway;
use Quickpay\HttpClient;

final class Gateways
{
    /** @var array<string, Gateway[]> keyed by api key prefix (process-lifetime cache) */
    private static array $cache = [];

    public function __construct(private readonly HttpClient $http) {}

    /** @return Gateway[] */
    public function list(): array
    {
        // The http client holds the config; we use a static cache per process.
        // The cache key is derived from a pointer to this http instance.
        $cacheKey = spl_object_id($this->http);

        if (!isset(self::$cache[$cacheKey])) {
            $response = $this->http->request('GET', '/gateways');
            self::$cache[$cacheKey] = array_map(
                fn(array $row) => Gateway::fromArray($row),
                $response['data'] ?? $response,
            );
        }

        return self::$cache[$cacheKey];
    }
}
