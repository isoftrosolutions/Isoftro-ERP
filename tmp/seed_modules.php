<?php
/**
 * Module System Seed & Verification Script
 * Run: php tmp/seed_modules.php
 */

require_once __DIR__ . '/../config/config.php';

echo "=== Module System Seed & Verification ===\n\n";

$db = getDBConnection();

// 1. Ensure dashboard module exists
echo "1. Seeding dashboard module...\n";
$db->exec("INSERT IGNORE INTO modules (name, label, is_core) VALUES ('dashboard', 'Dashboard', 1)");
echo "   Done.\n\n";

// 2. List all modules
echo "2. Current modules table:\n";
$stmt = $db->query("SELECT id, name, label, is_core, status FROM modules ORDER BY id");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo str_pad("ID", 5) . str_pad("Name", 20) . str_pad("Label", 40) . str_pad("Core", 6) . "Status\n";
echo str_repeat("-", 75) . "\n";
foreach ($modules as $m) {
    echo str_pad($m['id'], 5) . str_pad($m['name'], 20) . str_pad($m['label'], 40) . str_pad($m['is_core'], 6) . $m['status'] . "\n";
}

// 3. Check tenant module assignments
echo "\n3. Tenant module assignments:\n";
$stmt = $db->query("
    SELECT t.id as tenant_id, t.name as tenant_name, 
           GROUP_CONCAT(m.name ORDER BY m.name SEPARATOR ', ') as enabled_modules,
           COUNT(im.id) as module_count
    FROM tenants t
    LEFT JOIN institute_modules im ON t.id = im.tenant_id AND im.is_enabled = 1
    LEFT JOIN modules m ON im.module_id = m.id
    GROUP BY t.id, t.name
    ORDER BY t.id
");
$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tenants as $t) {
    echo "   Tenant #{$t['tenant_id']} ({$t['tenant_name']}): {$t['module_count']} modules\n";
    echo "   → {$t['enabled_modules']}\n\n";
}

// 4. Ensure all tenants have dashboard module assigned
echo "4. Ensuring all tenants have dashboard module assigned...\n";
$dashboardModule = $db->query("SELECT id FROM modules WHERE name = 'dashboard'")->fetch();
if ($dashboardModule) {
    $db->exec("
        INSERT IGNORE INTO institute_modules (tenant_id, module_id, is_enabled)
        SELECT t.id, {$dashboardModule['id']}, 1 FROM tenants t
    ");
    echo "   Done.\n";
} else {
    echo "   ERROR: Dashboard module not found!\n";
}

// 5. Verify hasModule function
echo "\n5. Testing hasModule() function:\n";
echo "   hasModule('dashboard') = " . (hasModule('dashboard') ? 'TRUE' : 'FALSE') . " (expected: TRUE - core)\n";
echo "   hasModule('academic')  = " . (hasModule('academic') ? 'TRUE' : 'FALSE') . " (expected: TRUE - core)\n";
echo "   hasModule('system')    = " . (hasModule('system') ? 'TRUE' : 'FALSE') . " (expected: TRUE - core)\n";
echo "   hasModule('staff')     = " . (hasModule('staff') ? 'TRUE' : 'FALSE') . " (expected: TRUE - core)\n";

// Test with a non-loaded session
$backup = $_SESSION['tenant_modules'] ?? null;
unset($_SESSION['tenant_modules']);
unset($_SESSION['tenant_id']);
echo "   hasModule('finance') [no session] = " . (hasModule('finance') ? 'TRUE' : 'FALSE') . " (expected: FALSE - deny by default)\n";
if ($backup !== null) {
    $_SESSION['tenant_modules'] = $backup;
}

// 6. Verify sidebar module names match DB
echo "\n6. Checking sidebar module names against DB...\n";
require_once APP_ROOT . '/app/Helpers/ia-sidebar-config.php';
$sidebarConfig = getIASidebarConfig();
$sidebarModules = [];
foreach ($sidebarConfig as $section) {
    foreach ($section['items'] as $item) {
        if (!empty($item['module'])) {
            $sidebarModules[$item['module']] = true;
        }
    }
}
$dbModuleNames = array_column($modules, 'name');
echo "   Sidebar references: " . implode(', ', array_keys($sidebarModules)) . "\n";
echo "   DB modules:         " . implode(', ', $dbModuleNames) . "\n";
$missing = array_diff(array_keys($sidebarModules), $dbModuleNames);
if (empty($missing)) {
    echo "   ✓ All sidebar modules exist in DB!\n";
} else {
    echo "   ✗ MISSING from DB: " . implode(', ', $missing) . "\n";
}

echo "\n=== Seed complete ===\n";
