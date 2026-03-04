<?php
require_once __DIR__ . '/config/config.php';
$db = getDBConnection();
$tid = 1; // Assuming tenant 1 based on previous context
$stmt = $db->prepare("SELECT id, course_id, name, status, deleted_at FROM batches WHERE tenant_id = :tid");
$stmt->execute(['tid' => $tid]);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($batches);

$stmtCourses = $db->prepare("SELECT id, name FROM courses WHERE tenant_id = :tid");
$stmtCourses->execute(['tid' => $tid]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);
print_r($courses);
