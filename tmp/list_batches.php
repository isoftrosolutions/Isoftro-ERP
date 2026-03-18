<?php
require_once __DIR__ . '/../config/config.php';
$db = getDBConnection();
$stmt = $db->query("SELECT b.id, b.name, b.tenant_id, c.name as course_name FROM batches b JOIN courses c ON b.course_id = c.id LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
