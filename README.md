# afconwave/sdk — Official PHP SDK

## Quick Start

```php
use AfconWave\AfconWave;

$afc = new AfconWave('afc_sk_test_your_key_here');
```

Sandbox keys: `afc_sk_test_`. Live keys: `afc_sk_live_`.

## Webhooks

Use the built-in helper (HMAC + 5-minute replay window):

```php
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_AFCONWAVE_SIGNATURE'] ?? '';

if (!AfconWave::verifyWebhookSignature($payload, $signature, $webhookSecret)) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
$name  = $event['type'] ?? $event['event'] ?? '';
```
