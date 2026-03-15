<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    // Simulate a student session for testing
    // We'll pick the first student with an active enrollment
    $student = $db->query("SELECT s.id, s.tenant_id, s.user_id, e.batch_id FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        die("No student with active enrollment found for testing.\n");
    }
    
    $studentId = $student['id'];
    $tenantId = $student['tenant_id'];
    $batchId = $student['batch_id'];
    
    echo "Testing Portal Fixes for Student ID: $studentId, Tenant ID: $tenantId, Batch ID: $batchId\n";
    echo "--------------------------------------------------------------------------------\n";

    // 1. Test Profile 'view'
    echo "[Testing Profile View]... ";
    $stmt = $db->prepare("
        SELECT s.*, b.name as batch_name, c.name as course_name
        FROM students s
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
        LEFT JOIN batches b ON e.batch_id = b.id
        LEFT JOIN courses c ON b.course_id = c.id
        WHERE s.id = :sid AND s.tenant_id = :tid
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    if ($stmt->fetch()) echo "SUCCESS\n"; else echo "FAILED\n";

    // 2. Test Classes 'today'
    echo "[Testing Classes Today]... ";
    $dayNum = 1; // Sunday
    $stmt = $db->prepare("
        SELECT t.*, s.name as subject_name
        FROM timetable_slots t
        LEFT JOIN subjects s ON t.subject_id = s.id
        WHERE t.batch_id = :bid AND t.tenant_id = :tid
    ");
    $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
    echo "SUCCESS (Found " . count($stmt->fetchAll()) . " slots)\n";

    // 3. Test Classes 'calendar'
    echo "[Testing Academic Calendar]... ";
    $stmt = $db->prepare("
        SELECT *, start_date as event_date FROM academic_calendar 
        WHERE tenant_id = :tid
          AND (batch = 'all' OR batch = 'batch' OR batch = :bid)
    ");
    $stmt->execute(['tid' => $tenantId, 'bid' => $batchId]);
    echo "SUCCESS (Found " . count($stmt->fetchAll()) . " events)\n";

    // 4. Test Leaderboard 'batch'
    echo "[Testing Leaderboard Batch]... ";
    try {
        $stmt = $db->prepare("
            SELECT s.id, u.name, ROUND(AVG(ea.score),1) AS avg_score
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
            LEFT JOIN exam_attempts ea ON ea.student_id = s.id AND ea.tenant_id = s.tenant_id
            WHERE e.batch_id = :bid AND s.tenant_id = :tid
            GROUP BY s.id, u.name
        ");
        $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
        echo "SUCCESS (Found " . count($stmt->fetchAll()) . " students)\n";
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "[CRITICAL ERROR] " . $e->getMessage() . "\n";
}
