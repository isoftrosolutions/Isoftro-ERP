<?php
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    echo "Connection successful: " . $redis->ping() . "\n";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
