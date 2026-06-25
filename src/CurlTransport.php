<?php

declare(strict_types=1);

namespace Quickpay;

interface CurlTransport
{
    /**
     * @param  array<string, string>  $headers
     * @return array{body: string, status: int, headers: array<string, string>}
     */
    public function send(string $method, string $url, string $body, array $headers): array;
}
