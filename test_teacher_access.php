<?php
require_once 'config/config.php';
$_SESSION['userData'] = [
    'id' => 1,
    'role' => 'teacher',
    'tenant_id' => 1
];

function testEndpoint($url) {
    echo "Testing $url...\n";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    // We can't easily Mock fetch here, but we can include the controller
    ob_start();
    include $url;
    $output = ob_get_clean();
    echo $output . "\n\n";
}

testEndpoint('app/Http/Controllers/Admin/courses.php');
testEndpoint('app/Http/Controllers/Admin/batches.php');
