<?php
require_once __DIR__ . '/config/config.php';
$db = getDBConnection();

// Emulate tenant_id = 5
$tenantId = 5;

echo "--- DIAGNOSING ADMISSION FORM DATA FOR TENANT $tenantId ---\n\n";

// Courses
$stmtCourses = $db->prepare("SELECT id, name, code FROM courses WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL ORDER BY name");
$stmtCourses->execute(['tid' => $tenantId]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

echo "COURSES FOUND (" . count($courses) . "):\n";
foreach ($courses as $c) {
    echo "ID: {$c['id']} | Name: {$c['name']} | Code: {$c['code']}\n";
}
echo "\n";

// Batches
$stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches WHERE tenant_id = :tid AND status IN ('active', 'upcoming') AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);

echo "BATCHES FOUND (" . count($batches) . "):\n";
foreach ($batches as $b) {
    echo "ID: {$b['id']} | Course ID: {$b['course_id']} | Name: {$b['name']} | Shift: {$b['shift']}\n";
}
echo "\n";

// Emulate JavaScript filtering
echo "--- EMULATING JS FILTERING ---\n";
foreach ($courses as $c) {
    $courseId = $c['id'];
    $matches = array_filter($batches, function($b) use ($courseId) {
        return $b['course_id'] == $courseId;
    });
    echo "Course: {$c['name']} (ID: $courseId) -> Found " . count($matches) . " batches.\n";
    foreach ($matches as $m) {
        echo "  - Batch ID: {$m['id']} | Name: {$m['name']}\n";
    }
}
