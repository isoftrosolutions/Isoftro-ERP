<?php
/**
 * Database Migration: Granular Module Expansion
 * Splits broad categories into individual feature-gating modules.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Helpers/DatabaseHelper.php';

use App\Helpers\DatabaseHelper;

header('Content-Type: text/plain');

$newModules = [
    'dashboard'     => 'Main Dashboard',
    'student'       => 'Student Management',
    'teacher'       => 'Teacher Management',
    'academic'      => 'Academic (Courses & Batches)',
    'attendance'    => 'Attendance Tracking',
    'exams'         => 'Examinations',
    'homework'      => 'Homework & Assignments',
    'inquiry'       => 'Inquiry Management',
    'finance'       => 'Fee Collection',
    'payroll'       => 'Staff Salary / Payroll',
    'frontdesk'     => 'Front Desk Operations',
    'lms'           => 'LMS (Study Materials)',
    'communication' => 'SMS & Notices',
    'library'       => 'Library Management',
    'report'        => 'Reports & Analytics',
    'system'        => 'System Settings'
];

try {
    $db = getDBConnection();
    $db->beginTransaction();

    echo "--- Migrating to Granular Modules ---\n";

    // 1. Ensure all new modules exist in 'modules' table
    $insertModule = $db->prepare("INSERT IGNORE INTO modules (name, label, status) VALUES (?, ?, 'active')");
    foreach ($newModules as $name => $label) {
        $insertModule->execute([$name, $label]);
        echo "Ensured module: $name\n";
    }

    // 2. Migration Mapping (Old -> New)
    $mapping = [
        'academic'    => ['student', 'teacher', 'academic'],
        'admissions'  => ['inquiry'],
        'exams'       => ['exams', 'homework'],
        'finance'     => ['finance', 'payroll'],
        'staff'       => ['frontdesk'],
        'reports'     => ['report']
    ];

    foreach ($mapping as $oldName => $newNames) {
        // Find existing assignments for old names
        $stmt = $db->prepare("
            SELECT tenant_id 
            FROM institute_modules im
            JOIN modules m ON im.module_id = m.id
            WHERE LOWER(m.name) = ? AND im.is_enabled = 1
        ");
        $stmt->execute([$oldName]);
        $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tenants as $tenantId) {
            foreach ($newNames as $newName) {
                // Get new module ID
                $getModId = $db->prepare("SELECT id FROM modules WHERE name = ?");
                $getModId->execute([$newName]);
                $modId = $getModId->fetchColumn();

                if ($modId) {
                    $insertMapping = $db->prepare("
                        INSERT INTO institute_modules (tenant_id, module_id, is_enabled)
                        VALUES (?, ?, 1)
                        ON DUPLICATE KEY UPDATE is_enabled = 1
                    ");
                    $insertMapping->execute([$tenantId, $modId]);
                    echo "Mapped tenant $tenantId: $oldName -> $newName\n";
                }
            }
        }
    }

    $db->commit();
    echo "\nMigration Successful.\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
