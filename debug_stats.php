<?php
require 'config/config.php';
try {
    $stats = \App\Helpers\StatsHelper::getSuperAdminStats();
    echo "Stats result: " . ($stats ? "Success" : "NULL") . "\n";
    if (!$stats) {
        echo "Check your error log records.\n";
    } else {
        echo "Total Tenants: " . $stats['totalTenants'] . "\n";
    }
} catch (Exception $e) {
    echo "Caught Exception: " . $e->getMessage() . "\n";
}
