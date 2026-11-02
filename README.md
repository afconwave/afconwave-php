# afconwave/afconwave-php

Official **PHP SDK** for [AfconWave API v2](https://github.com/afconwave/afconwave-api).

Keys: `afcw_test_…` · `afcw_live_…`  
Auth: `X-API-Key`  
Refs: `AFCW-IN-…` / `AFCW-OUT-…`

```bash
composer require afconwave/afconwave-php
```

## Quick start

```php
use AfconWave\AfconWave;

$afc = new AfconWave('afcw_test_your_key_here');

$deposit = $afc->deposits()->create([
    'amount' => 5000,
    'country_code' => 'CM',
    'currency' => 'XAF',
    'payment_method' => 'MTN',
    'mobile' => '670000000',
    'mode' => 'direct',
    'idempotency_key' => 'order-123',
]);

$payout = $afc->withdrawals()->create([
    'amount' => 2000,
    'currency' => 'XAF',
    'country_code' => 'CM',
    'payment_method' => 'MTN',
    'beneficiary_name' => 'Jane Doe',
    'mobile_no' => '670000001',
]);

$wallets = $afc->wallets()->list();
```

## Resources

`deposits`, `withdrawals`, `wallets`, `transactions`, `refunds`, `disputes`, `airtime`, `data`, `paymentLinks`, `rails`

Deprecated: `createPayment` / `payments()` → deposits; `createPayout` / `payouts()` → withdrawals.

## Breaking changes (v2)

- Auth is **`X-API-Key`**, not Bearer.
- Base path `/v1`.
- Corridor fields required (`country_code`, `payment_method`, etc.).

## License

MIT
