<?php
require_once __DIR__ . '/config/config.php';
header('Content-Type: text/plain');

echo "APP_URL: " . APP_URL . "\n";
echo "APP_ROOT: " . APP_ROOT . "\n";
echo "SESSION_LOGGED__IN: " . (isset($_SESSION['userData']) ? 'YES' : 'NO') . "\n";
if (isset($_SESSION['userData'])) {
    echo "USER_ROLE: " . $_SESSION['userData']['role'] . "\n";
    echo "TENANT_ID: " . $_SESSION['userData']['tenant_id'] . "\n";
}

// Check if we can reach an API endpoint locally
$url = APP_URL . '/api/admin/rooms?tenant_id=' . ($_SESSION['userData']['tenant_id'] ?? '');
echo "Checking URL: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Pass session cookie
$session_name = session_name();
$session_id = session_id();
curl_setopt($ch, CURLOPT_COOKIE, "$session_name=$session_id");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP CODE: $httpCode\n";
echo "RAW RESPONSE START: " . substr($response, 0, 100) . "\n";
