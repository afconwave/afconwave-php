<?php

namespace AfconWave;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use AfconWave\Exceptions\AfconWaveException;
use AfconWave\Exceptions\AuthException;

class AfconWave
{
    /** @var Client */
    private $client;

    /** @var string */
    private $secretKey;

    public function __construct(string $secretKey, string $baseUrl = 'https://api.afconwave.com/v1')
    {
        $this->secretKey = $secretKey;
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'timeout' => 30.0,
        ]);
    }

    /**
     * Verifies an incoming webhook signature.
     */
    public static function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Internal request handler with error wrapping.
     */
    public function request(string $method, string $uri, array $options = [])
    {
        try {
            $response = $this->client->request($method, $uri, $options);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? $data;
        } catch (GuzzleException $e) {
            $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
            $status = $response ? $response->getStatusCode() : 500;
            $body = $response ? json_decode($response->getBody()->getContents(), true) : [];
            $message = $body['error'] ?? $e->getMessage();

            if ($status === 401) {
                throw new AuthException($message);
            }
            throw new AfconWaveException($message, $status);
        }
    }

    // ─── Top-level Convenience Methods (Matches README) ─────────────────────

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

    // ─── Resource-based API ──────────────────────────────────────────────────

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
            public function retrieve(string $id) { return $this->parent->request('GET', 'payouts/' . $id); }
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
            public function list() { return $this->parent->request('GET', 'refunds'); }
        };
    }

    public function disputes()
    {
        return new class($this) {
            private $parent;
            public function __construct($parent) { $this->parent = $parent; }
            public function open(array $data) { return $this->parent->request('POST', 'disputes', ['json' => $data]); }
            public function list() { return $this->parent->request('GET', 'disputes'); }
            public function resolve(string $id, array $data) { return $this->parent->request('POST', "disputes/{$id}/resolve", ['json' => $data]); }
        };
    }
}
