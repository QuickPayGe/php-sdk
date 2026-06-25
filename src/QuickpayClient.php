<?php

declare(strict_types=1);

namespace Quickpay;

use Quickpay\Resources\CheckoutLinks;
use Quickpay\Resources\Gateways;
use Quickpay\Resources\Payments;
use Quickpay\Resources\Products;

final class QuickpayClient
{
    private Config $config;
    private HttpClient $http;

    private ?Payments $payments       = null;
    private ?CheckoutLinks $checkoutLinks = null;
    private ?Products $products       = null;
    private ?Gateways $gateways       = null;

    public function __construct(string|Config $apiKey, ?CurlTransport $transport = null)
    {
        $this->config = is_string($apiKey) ? new Config($apiKey) : $apiKey;
        $this->http   = new HttpClient($this->config, $transport);
    }

    public function __get(string $name): Payments|CheckoutLinks|Products|Gateways
    {
        return match ($name) {
            'payments'      => $this->payments      ??= new Payments($this->http),
            'checkoutLinks' => $this->checkoutLinks ??= new CheckoutLinks($this->http),
            'products'      => $this->products      ??= new Products($this->http),
            'gateways'      => $this->gateways      ??= new Gateways($this->http),
            default         => throw new \BadMethodCallException("Unknown resource: $name"),
        };
    }
}
