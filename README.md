# quickpay/php-sdk

Official PHP SDK for the [Quickpay.ge](https://quickpay.ge) payment gateway API.

## Installation

```bash
composer require quickpay/php-sdk
```

Requires PHP 8.1+. No external runtime dependencies.

## Quick Start

```php
use Quickpay\QuickpayClient;

$client = new QuickpayClient('qpk_live_your_api_key_here');

// Create a payment
$payment = $client->payments->create([
    'amount'            => 99.99,
    'currency'          => 'GEL',
    'description'       => 'Order #1234',
    'merchant_order_id' => 'order-1234',
    'idempotency_key'   => 'order-1234-attempt-1',
    'customer'          => [
        'name'  => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+995599123456',
    ],
]);

// Redirect customer to the payment page
header('Location: ' . $payment->paymentUrl);
exit;
```

## Webhook Verification

```php
use Quickpay\Webhook\WebhookVerifier;
use Quickpay\Exceptions\QuickpayException;

$verifier = new WebhookVerifier();

try {
    $event = $verifier->verify(
        secret: 'your_webhook_secret',
        rawPayload: file_get_contents('php://input'),
        signatureHeader: $_SERVER['HTTP_QUICKPAY_SIGNATURE'] ?? ''
    );

    if ($event->type === 'payment.paid' && $event->payment !== null) {
        // Mark order as paid in your database
        $uuid = $event->payment->uuid;
    }
} catch (QuickpayException $e) {
    http_response_code(400);
    exit;
}

http_response_code(200);
```

## Error Handling

```php
use Quickpay\QuickpayClient;
use Quickpay\Exceptions\AuthException;
use Quickpay\Exceptions\ValidationException;
use Quickpay\Exceptions\RateLimitException;
use Quickpay\Exceptions\NotFoundException;
use Quickpay\Exceptions\ApiException;
use Quickpay\Exceptions\QuickpayException;

$client = new QuickpayClient('qpk_live_...');

try {
    $payment = $client->payments->get('some-uuid');
} catch (AuthException $e) {
    // 401/403 — invalid or missing API key, suspended account
} catch (ValidationException $e) {
    // 422 — field-level errors
    foreach ($e->errors as $field => $messages) {
        echo "$field: " . implode(', ', $messages) . "\n";
    }
} catch (RateLimitException $e) {
    // 429 — retry after N seconds
    sleep($e->retryAfter());
} catch (NotFoundException $e) {
    // 404 — payment / resource not found
} catch (ApiException $e) {
    // 5xx — server error (retried internally up to 3 times)
} catch (QuickpayException $e) {
    // Catch-all for any SDK error
}
```

## All Resources

| Resource | Methods |
|---|---|
| `$client->payments` | `create()`, `get()`, `list()`, `refund()` |
| `$client->checkoutLinks` | `create()`, `get()`, `list()` |
| `$client->products` | `create()`, `get()`, `list()`, `update()` |
| `$client->gateways` | `list()` |

## Configuration

```php
use Quickpay\Config;
use Quickpay\QuickpayClient;

$config = new Config(
    apiKey:     'qpk_live_...',
    baseUrl:    'https://api.quickpay.ge/v1',
    timeout:    30,
    siteDomain: 'myshop.ge'
);

$client = new QuickpayClient($config);
```

| Option | Default | Description |
|---|---|---|
| `apiKey` | required | Your API key (`qpk_live_...` or `qpk_test_...`) |
| `baseUrl` | `https://api.quickpay.ge/v1` | API base URL |
| `timeout` | `30` | cURL timeout in seconds |
| `siteDomain` | `''` | Sent as `X-Site-Domain` header for per-domain gateway licensing |

## License

MIT
