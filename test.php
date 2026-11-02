<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/AfconWave.php';

try {
    $client = new \AfconWave\AfconWave('afcw_test_smoke_only');
    echo "PHP SDK Instantiated Successfully!\n";
} catch (Exception $e) {
    echo "Failed to instantiate PHP SDK: " . $e->getMessage() . "\n";
    exit(1);
}
