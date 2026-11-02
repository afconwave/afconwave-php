<?php

namespace AfconWave;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use AfconWave\Exceptions\AfconWaveException;
use AfconWave\Exceptions\AuthException;

class AfconWave
{
    public const VERSION = '2.0.0';

    /** @var Client */
    private $client;

    /** @var string */
    private $secretKey;

    public function __construct(string $secretKey, array $options = [])
    {
        if ($secretKey === '' || strpos($secretKey, 'afcw_') !== 0) {
            throw new AuthException('secretKey must be an afcw_test_... or afcw_live_... key');
        }

        $this->secretKey = $secretKey;

        $sandbox = $options['sandbox'] ?? false;
        $baseUrl = getenv('AFCONWAVE_BASE_URL')
            ?: ($options['base_url'] ?? null)
            ?: ($sandbox ? 'https://sandbox.api.afconwave.com/v1' : 'https://api.afconwave.com/v1');

        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'headers' => [
                'X-API-Key'    => $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => 'AfconWave-PHP-SDK/' . self::VERSION,
            ],
            'timeout' => $options['timeout'] ?? 30.0,
        ]);
    }

    public static function verifyWebhookSignature(
        string $payload,
        string $signature,
        string $secret,
        int $tolerance = 300,
        bool $timestampPrefixed = false,
        ?string $timestamp = null
    ): bool {
        if ($signature === '' || $secret === '') {
            return false;
        }

        $message = ($timestampPrefixed && $timestamp !== null)
            ? $timestamp . '.' . $payload
            : $payload;

        $expected = hash_hmac('sha256', $message, $secret);
        if (!hash_equals($expected, $signature)) {
            return false;
        }

        if ($timestamp !== null) {
            $ts = (float) $timestamp;
            $webhookTime = $ts > 1e12 ? $ts / 1000.0 : $ts;
            if (abs(time() - $webhookTime) > $tolerance) {
                return false;
            }
        }

        return true;
    }

    public function request(string $method, string $uri, array $options = [])
    {
        try {
            $response = $this->client->request($method, ltrim($uri, '/'), $options);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data ?? [];
        } catch (GuzzleException $e) {
            $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
            $status   = $response ? $response->getStatusCode() : 500;
            $body     = $response ? json_decode($response->getBody()->getContents(), true) : [];
            $message  = $body['error'] ?? $body['message'] ?? $e->getMessage();

            if ($status === 401) {
                throw new AuthException($message);
            }
            throw new AfconWaveException($message, $status);
        }
    }

    private function withIdempotency(array $options, ?string $key): array
    {
        if ($key) {
            $options['headers'] = array_merge($options['headers'] ?? [], [
                'Idempotency-Key' => $key,
            ]);
        }
        return $options;
    }

    // ── Deposits ────────────────────────────────────────────────────────────

    public function deposits()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }

            public function create(array $data)
            {
                $opts = $this->parent->withIdempotency(['json' => $data], $data['idempotency_key'] ?? null);
                return $this->parent->request('POST', 'deposits', $opts);
            }

            public function retrieve(string $reference)
            {
                return $this->parent->request('GET', 'deposits/' . $reference);
            }

            public function list(array $params = [])
            {
                return $this->parent->request('GET', 'deposits', ['query' => $params]);
            }

            public function sync(string $reference)
            {
                return $this->parent->request('POST', 'deposits/' . $reference . '/sync');
            }
        };
    }

    // ── Withdrawals ─────────────────────────────────────────────────────────

    public function withdrawals()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }

            public function create(array $data)
            {
                $opts = $this->parent->withIdempotency(['json' => $data], $data['idempotency_key'] ?? null);
                return $this->parent->request('POST', 'withdrawals', $opts);
            }

            public function list(array $params = [])
            {
                return $this->parent->request('GET', 'withdrawals', ['query' => $params]);
            }

            public function sync(string $reference)
            {
                return $this->parent->request('POST', 'withdrawals/' . $reference . '/sync');
            }

            public function methods(string $countryCode)
            {
                return $this->parent->request('GET', 'withdrawals/methods', [
                    'query' => ['country_code' => $countryCode],
                ]);
            }

            public function nigeriaBanks()
            {
                return $this->parent->request('GET', 'withdrawals/nigeria-banks');
            }
        };
    }

    // ── Wallets ─────────────────────────────────────────────────────────────

    public function wallets()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }

            public function list(array $params = [])
            {
                return $this->parent->request('GET', 'wallets', ['query' => $params]);
            }

            public function ensure(string $currency, ?string $businessId = null)
            {
                $body = ['currency' => $currency];
                if ($businessId) {
                    $body['business_id'] = $businessId;
                }
                return $this->parent->request('POST', 'wallets/ensure', ['json' => $body]);
            }

            public function convert(array $data)
            {
                $opts = $this->parent->withIdempotency(['json' => $data], $data['idempotency_key'] ?? null);
                return $this->parent->request('POST', 'wallets/convert', $opts);
            }

            public function transfer(array $data)
            {
                $opts = $this->parent->withIdempotency(['json' => $data], $data['idempotency_key'] ?? null);
                return $this->parent->request('POST', 'wallets/transfer', $opts);
            }

            public function rates()
            {
                return $this->parent->request('GET', 'wallets/rates');
            }
        };
    }

    // ── Transactions ────────────────────────────────────────────────────────

    public function transactions()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }

            public function list(array $params = [])
            {
                return $this->parent->request('GET', 'transactions', ['query' => $params]);
            }

            public function retrieve(string $idOrRef)
            {
                return $this->parent->request('GET', 'transactions/' . $idOrRef);
            }

            public function recent(int $limit = 4)
            {
                return $this->parent->request('GET', 'transactions/recent', ['query' => ['limit' => $limit]]);
            }
        };
    }

    public function refunds()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function create(array $data) { return $this->parent->request('POST', 'refunds', ['json' => $data]); }
            public function list(array $params = []) { return $this->parent->request('GET', 'refunds', ['query' => $params]); }
        };
    }

    public function disputes()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function open(array $data) { return $this->parent->request('POST', 'disputes', ['json' => $data]); }
            public function list(array $params = []) { return $this->parent->request('GET', 'disputes', ['query' => $params]); }
        };
    }

    public function airtime()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function purchase(array $data) { return $this->parent->request('POST', 'airtime', ['json' => $data]); }
        };
    }

    public function data()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function purchase(array $data) { return $this->parent->request('POST', 'data', ['json' => $data]); }
        };
    }

    public function paymentLinks()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function create(array $data) { return $this->parent->request('POST', 'payment-links', ['json' => $data]); }
            public function list() { return $this->parent->request('GET', 'payment-links'); }
        };
    }

    public function rails()
    {
        $self = $this;
        return new class($self) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function payoutMethods(string $countryCode)
            {
                return $this->parent->request('GET', 'rails/payout-methods', [
                    'query' => ['country_code' => $countryCode],
                ]);
            }
        };
    }

    // ── Back-compat ─────────────────────────────────────────────────────────

    public function getBalances(array $params = []): array
    {
        $res = $this->wallets()->list($params);
        return $res['wallets'] ?? $res;
    }

    /** @deprecated Use deposits()->create() */
    public function createPayment(array $data)
    {
        return $this->deposits()->create($data);
    }

    /** @deprecated Use withdrawals()->create() */
    public function createPayout(array $data)
    {
        return $this->withdrawals()->create($data);
    }

    /** @deprecated */
    public function payments() { return $this->deposits(); }

    /** @deprecated */
    public function payouts() { return $this->withdrawals(); }
}
