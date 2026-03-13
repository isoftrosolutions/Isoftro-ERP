<?php
/**
 * CLI Script: Fee Automation
 * Generates monthly invoices for all active students based on batch fee settings.
 */

require_once __DIR__ . '/../vendor/autoload.php';
// Boot minimal app context if needed, or use models directly

use App\Models\Student;
use App\Models\FeeRecord;
use App\Services\FeeCalculationService;

// This script should be run with a tenant_id context if multi-tenant
// For now, it will process all tenants if not specified
$targetTenantId = $argv[1] ?? null;

function processTenant($tenantId)
{
    echo "Processing Tenant: $tenantId\n";

    // 1. Get all active students
    // Assuming a Student::getActive($tenantId) exists or raw query
    $db = \App\Support\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, name, batch_id FROM students WHERE tenant_id = ? AND status = 'active'");
    $stmt->execute([$tenantId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $service = new FeeCalculationService();
    $currentMonth = date('Y-m');

    foreach ($students as $student) {
        try {
            echo " - Student: {$student['name']} ({$student['id']})\n";
            $result = $service->generateMonthlyFees($student['id'], $tenantId);
            echo "   Result: " . json_encode($result) . "\n";
        }
        catch (Exception $e) {
            echo "   Error: " . $e->getMessage() . "\n";
        }
    }
}

// Logic to iterate through tenants or specific one
if ($targetTenantId) {
    processTenant($targetTenantId);
}
else {
    // Get all tenants
    $db = \App\Support\Database::getInstance()->getConnection();
    $tenants = $db->query("SELECT id FROM tenants")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tenants as $tid) {
        processTenant($tid);
    }
}

echo "Automation Complete.\n";
