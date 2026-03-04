<?php
require_once 'config/config.php';

// Mock session for testing
$_SESSION['userData'] = [
    'id' => 1,
    'tenant_id' => 1,
    'role' => 'instituteadmin'
];

try {
    // Attempt to require the inquiries controller
    ob_start();
    require_once 'app/Http/Controllers/Admin/inquiries.php';
    $output = ob_get_clean();
    
    echo "API Response:\n";
    echo $output . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
