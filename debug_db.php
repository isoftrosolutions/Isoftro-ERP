<?php
require_once __DIR__ . '/config/config.php';
$db = getDBConnection();

echo "Checking tenants...\n";
$stmt = $db->query("SELECT id, name FROM tenants");
print_r($stmt->fetchAll());

echo "\nChecking students (all tenants)...\n";
$stmt = $db->query("SELECT id, tenant_id, full_name, batch_id FROM students LIMIT 10");
$students = $stmt->fetchAll();
print_r($students);

if (count($students) > 0) {
    echo "\nChecking fee summaries for these students...\n";
    foreach ($students as $s) {
        $stmt = $db->prepare("SELECT * FROM student_fee_summary WHERE student_id = :sid");
        $stmt->execute(['sid' => $s['id']]);
        $summaries = $stmt->fetchAll();
        echo "Student ID {$s['id']} has " . count($summaries) . " summaries.\n";
        foreach ($summaries as $sm) {
            echo "  - Summary ID {$sm['id']}, Enrollment ID {$sm['enrollment_id']}, Fee Status: {$sm['fee_status']}\n";
        }
    }
} else {
    echo "\nNo students found in the database.\n";
}
