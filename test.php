<?php
require_once __DIR__ . '/src/AfconWave.php';

try {
    $client = new \AfconWave\AfconWave('sk_test_123');
    echo "PHP SDK Instantiated Successfully!\n";
    echo "Services loaded: Payments, Payouts, Crypto\n";
} catch (Exception $e) {
    echo "Failed to instantiate PHP SDK: " . $e->getMessage() . "\n";
    exit(1);
}
