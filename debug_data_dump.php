<?php
require_once 'config/config.php';
$db = getDBConnection();
echo "--- COURSES ---\n";
$stmt = $db->query("SELECT id, name, tenant_id, is_active, status, deleted_at FROM courses");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
echo "\n--- BATCHES ---\n";
$stmt = $db->query("SELECT id, name, course_id, tenant_id, status, deleted_at FROM batches");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
echo "\n--- USERS ---\n";
$stmt = $db->query("SELECT id, name, role, tenant_id FROM users WHERE id IN (123, 125)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
echo "\n--- HOMEWORK (TID=3) ---\n";
$stmt = $db->query("SELECT id, title, created_by FROM homework WHERE tenant_id=3");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
