<?php
require_once __DIR__ . '/config/config.php';
$db = getDBConnection();

echo "--- TENANTS ---\n";
$stmt = $db->query("SELECT id, name FROM tenants");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- ALL BATCHES ---\n";
$stmt = $db->query("SELECT id, tenant_id, name, status FROM batches");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- ALL COURSES ---\n";
$stmt = $db->query("SELECT id, tenant_id, name FROM courses");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
