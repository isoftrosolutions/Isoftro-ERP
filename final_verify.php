<?php
define('APP_NAME', 'Hamro ERP');
require_once __DIR__ . '/config/config.php';

$controllers = [
    'app/Http/Controllers/Admin/students.php',
    'app/Http/Controllers/Admin/batches.php',
    'app/Http/Controllers/FrontDesk/students.php'
];

foreach ($controllers as $ctrl) {
    echo "Verifying $ctrl...\n";
    $filepath = __DIR__ . '/' . $ctrl;
    $content = file_get_contents($filepath);
    
    // Check for require_once patterns
    preg_match_all("/require_once __DIR__ \. '([^']+)'/", $content, $matches);
    
    foreach ($matches[1] as $path) {
        $absPath = realpath(dirname($filepath) . '/' . $path);
        if ($absPath && file_exists($absPath)) {
            echo "  OK: $path -> $absPath\n";
        } else {
            echo "  FAILED: $path (resolved to " . (dirname($filepath) . '/' . $path) . ")\n";
        }
    }
}
