# afconwave/sdk — Official PHP SDK

> The official PHP client library for the AfconWave Payments API.

[![Packagist](https://img.shields.io/packagist/v/afconwave/sdk.svg)](https://packagist.org/packages/afconwave/sdk)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

---

## Features

- ✅ PHP 7.4+ and PHP 8.x compatible
- 🌍 Payments, Payouts, and Refunds
- 🔒 Secure, server-side only API key handling
- 🔔 Webhook HMAC-SHA256 signature verification
- 🧪 Sandbox-ready with test keys
- 📦 PSR-4 autoloaded via Composer

---

## Requirements

- PHP 7.4 or higher
- Composer
- `guzzlehttp/guzzle` (auto-installed)

---

## Installation

```bash
composer require afconwave/sdk
```

---

## Quick Start

```php
<?php

require "vendor/autoload.php";

use AfconWave\AfconWave;

$afw = new AfconWave('sk_test_your_key_here');
```

---

## Usage Guide

### Create a Payment

```php
$payment = $afw->createPayment([
    'amount'       => 5000,           // Amount in minor units (5000 = 50 XAF)
    'currency'     => 'XAF',
    'description'  => 'Order #1234',
    'callback_url' => 'https://yoursite.com/payment/callback',
    'customer'     => [
        'name'  => 'Jean Dupont',
        'email' => 'jean@example.com',
        'phone' => '+237600000000',
    ],
    'metadata' => [
        'order_id' => 'ORD-1234',
    ],
]);

echo $payment['checkout_url']; // Redirect user here
echo $payment['id'];           // e.g., pay_507f191e8180f
```

### Retrieve a Payment

```php
$payment = $afw->retrievePayment('pay_507f191e8180f');

echo $payment['status'];   // "pending" | "success" | "failed"
echo $payment['amount'];
echo $payment['paid_at'];
```

### Create a Payout

```php
$payout = $afw->createPayout([
    'amount'    => 10000,
    'currency'  => 'XAF',
    'recipient' => [
        'phone'   => '+237600000001',
        'network' => 'MTN',   // "MTN" | "ORANGE" | "MOOV" | "WAVE"
        'name'    => 'Marie Kamga',
    ],
    'reference' => 'PAYOUT-REF-001',
]);

echo $payout['status']; // "pending" | "success" | "failed"
```

### List Payments

```php
$result = $afw->listPayments([
    'limit'  => 20,
    'status' => 'success',
]);

foreach ($result['data'] as $payment) {
    echo $payment['id'] . ' — ' . $payment['status'] . PHP_EOL;
}
```

---

## Webhook Verification

Always verify that incoming webhooks are genuinely from AfconWave.

```php
<?php

// Your webhook endpoint (e.g., yoursite.com/webhooks/afconwave)

$webhookSecret = 'your_webhook_secret_here';
$payload       = file_get_contents('php://input');
$signature     = $_SERVER['HTTP_X_AFCONWAVE_SIGNATURE'] ?? '';

$expected = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expected, $signature)) {
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

$event = json_decode($payload, true);

switch ($event['event']) {
    case 'payment.success':
        // Fulfill the order
        error_log('Payment received: ' . $event['data']['id']);
        break;
    case 'payment.failed':
        // Notify the customer
        break;
    case 'payout.success':
        // Update your records
        break;
}

http_response_code(200);
echo 'OK';
```

---

## Error Handling

```php
use AfconWave\AfconWave;
use AfconWave\Exceptions\AfconWaveException;
use AfconWave\Exceptions\AuthException;

try {
    $payment = $afw->createPayment([...]);
} catch (AuthException $e) {
    echo 'Invalid API Key: ' . $e->getMessage();
} catch (AfconWaveException $e) {
    echo 'API Error ' . $e->getStatusCode() . ': ' . $e->getMessage();
} catch (\Exception $e) {
    echo 'Unexpected error: ' . $e->getMessage();
}
```

---

## Configuration

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$secretKey` | `string` | **required** | Your AfconWave secret API key |
| `$baseUrl` | `string` | `https://api.afconwave.com/v1` | API base URL |

---

## Sandbox / Testing

Use test keys prefixed with `sk_test_` to run inside sandbox mode.

```php
$afw = new AfconWave('sk_test_...');
```

---

## Laravel Integration (Example)

Create a service and bind it in your `AppServiceProvider`:

```php
// config/services.php
'afconwave' => [
    'secret_key' => env('AFCONWAVE_SECRET_KEY'),
],

// AppServiceProvider.php
use AfconWave\AfconWave;

$this->app->singleton(AfconWave::class, function () {
    return new AfconWave(config('services.afconwave.secret_key'));
});

// In a controller
class PaymentController extends Controller
{
    public function __construct(private AfconWave $afw) {}

    public function checkout(Request $request)
    {
        $payment = $this->afw->createPayment([
            'amount'       => $request->amount,
            'currency'     => 'XAF',
            'callback_url' => route('payment.callback'),
        ]);

        return redirect($payment['checkout_url']);
    }
}
```

---

## Documentation

Full API documentation: [docs.afconwave.com](https://docs.afconwave.com)

---

## License

MIT © AfconWave
