<?php

namespace AfconWave;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use AfconWave\Exceptions\AfconWaveException;
use AfconWave\Exceptions\AuthException;

/**
 * AfconWave PHP SDK v1.1.0
 * Official Global & African payments, payouts, crypto, refunds & disputes client.
 */
class AfconWave
{
    public const VERSION = '1.1.0';

    /** @var Client */
    private $client;

    /** @var string */
    private $secretKey;

    public function __construct(string $secretKey, string $baseUrl = 'https://api.afconwave.com/v1')
    {
        $this->secretKey = $secretKey;
        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'User-Agent'    => 'AfconWave-PHP-SDK/' . self::VERSION,
            ],
            'timeout' => 30.0,
        ]);
    }

    /**
     * Verifies an incoming webhook signature and checks for replay attacks.
     *
     * @param string $payload   Raw request body string (do not decode)
     * @param string $signature The X-AfconWave-Signature header value
     * @param string $secret    Your webhook secret
     * @param int    $tolerance Max age in seconds (default 300 = 5 min)
     */
    public static function verifyWebhookSignature(
        string $payload,
        string $signature,
        string $secret,
        int $tolerance = 300
    ): bool {
        if (empty($signature) || empty($secret)) {
            return false;
        }

        // 1. Verify HMAC-SHA256 signature
        $expected = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            return false;
        }

        // 2. Replay protection — support both ms and seconds timestamps
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return true; // Non-JSON payload: signature-only check
        }

        $timestamp = $data['timestamp']
            ?? $data['created_at']
            ?? $data['createdAt']
            ?? null;

        if ($timestamp !== null) {
            $ts = (int) $timestamp;
            // Normalize: if > 10^10 it's milliseconds
            $webhookTime = $ts > 10_000_000_000 ? (int) ($ts / 1000) : $ts;
            $age = abs(time() - $webhookTime);
            if ($age > $tolerance) {
                return false;
            }
        }

        return true;
    }

    /**
     * Internal HTTP request handler with structured error wrapping.
     *
     * @return mixed Decoded response data
     * @throws AfconWaveException|AuthException
     */
    public function request(string $method, string $uri, array $options = [])
    {
        try {
            $response = $this->client->request($method, ltrim($uri, '/'), $options);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? $data;
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

    // ─── Account ──────────────────────────────────────────────────────────────

    /**
     * Retrieves account balances for all supported currencies.
     */
    public function getBalances(): array
    {
        return $this->request('GET', 'balances');
    }

    // ─── Top-level Convenience Methods ────────────────────────────────────────

    public function createPayment(array $data)
    {
        return $this->request('POST', 'payments', ['json' => $data]);
    }

    public function retrievePayment(string $id)
    {
        return $this->request('GET', 'payments/' . $id);
    }

    public function listPayments(array $params = [])
    {
        return $this->request('GET', 'payments', ['query' => $params]);
    }

    public function createPayout(array $data)
    {
        return $this->request('POST', 'payouts', ['json' => $data]);
    }

    public function retrievePayout(string $id)
    {
        return $this->request('GET', 'payouts/' . $id);
    }

    public function listPayouts(array $params = [])
    {
        return $this->request('GET', 'payouts', ['query' => $params]);
    }

    // ─── Resource-based API ───────────────────────────────────────────────────

    public function payments()
    {
        return new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function create(array $data) { return $this->parent->createPayment($data); }
            public function retrieve(string $id) { return $this->parent->retrievePayment($id); }
            public function list(array $params = []) { return $this->parent->listPayments($params); }
        };
    }

    public function payouts()
    {
        return new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function create(array $data) { return $this->parent->createPayout($data); }
            public function retrieve(string $id) { return $this->parent->retrievePayout($id); }
            public function list(array $params = []) { return $this->parent->listPayouts($params); }
        };
    }

    public function crypto()
    {
        return new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function buy(array $data) { return $this->parent->request('POST', 'crypto/buy', ['json' => $data]); }
        };
    }

    public function refunds()
    {
        return new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function create(array $data) { return $this->parent->request('POST', 'refunds', ['json' => $data]); }
            public function list(array $params = []) { return $this->parent->request('GET', 'refunds', ['query' => $params]); }
        };
    }

    public function disputes()
    {
        return new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function open(array $data) { return $this->parent->request('POST', 'disputes', ['json' => $data]); }
            public function list(array $params = []) { return $this->parent->request('GET', 'disputes', ['query' => $params]); }
            public function resolve(string $id, array $data) {
                return $this->parent->request('POST', "disputes/{$id}/resolve", ['json' => $data]);
            }
        };
    }
}
