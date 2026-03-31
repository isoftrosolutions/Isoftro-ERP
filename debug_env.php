<?php
require_once __DIR__ . '/config/config.php';

echo "APP_URL: " . APP_URL . "\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "APP_ENV: " . APP_ENV . "\n";
echo "SESSION_DRIVER: " . getenv('SESSION_DRIVER') . "\n";
echo "CACHE_DRIVER: " . getenv('CACHE_DRIVER') . "\n";
echo "REDIS_HOST: " . getenv('REDIS_HOST') . "\n";
echo "SESSION STATUS: " . session_status() . "\n";
