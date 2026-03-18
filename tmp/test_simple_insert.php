<?php
require_once __DIR__ . '/../config/config.php';
$db = getDBConnection();

try {
    $db->beginTransaction();
    echo "Inserting test enrollment...\n";
    $stmt = $db->prepare("INSERT INTO enrollments (tenant_id, student_id, batch_id, enrollment_id, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([3, 999, 1, 'TEST-ID-' . time(), date('Y-m-d'), 'active']);
    echo "Success! Last insert ID: " . $db->lastInsertId() . "\n";
    $db->rollBack();
    echo "Rolled back.\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    if ($db->inTransaction()) $db->rollBack();
}
