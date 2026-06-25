<?php

declare(strict_types=1);

namespace Quickpay;

final class DefaultCurlTransport implements CurlTransport
{
    public function send(string $method, string $url, string $body, array $headers): array
    {
        $ch = curl_init();

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = "$name: $value";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // Capture response headers
        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$responseHeaders) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($header);
        });

        $responseBody = curl_exec($ch);
        $status       = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exceptions\ApiException("cURL error: $error", 'curl_error', 0);
        }

        curl_close($ch);

        return [
            'body'    => $responseBody,
            'status'  => $status,
            'headers' => $responseHeaders,
        ];
    }
}
