<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    // Simulate a student session for testing
    // We'll pick the first student from the DB
    $student = $db->query("SELECT s.id, s.tenant_id, s.user_id FROM students s JOIN enrollments e ON s.id = e.student_id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        die("No student with active enrollment found for testing.\n");
    }
    
    $studentId = $student['id'];
    $tenantId = $student['tenant_id'];
    
    echo "Testing Profile Fixes for Student ID: $studentId, Tenant ID: $tenantId\n";
    
    // 1. Test 'view' action query logic
    $stmt = $db->prepare("
        SELECT s.*, 
               b.name as batch_name, b.start_date as batch_start,
               c.name as course_name, 
               COALESCE(CONCAT(c.duration_months, ' Months'), CONCAT(c.duration_weeks, ' Weeks'), 'N/A') as duration,
               c.fee as course_fee,
               t.name as institute_name, t.address as institute_address,
               t.phone as institute_phone, t.email as institute_email,
               t.logo_path as institute_logo,
               u.name as name, u.email as login_email, u.phone as login_phone,
               u.last_login_at
        FROM students s
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
        LEFT JOIN batches b ON e.batch_id = b.id
        LEFT JOIN courses c ON b.course_id = c.id
        LEFT JOIN tenants t ON s.tenant_id = t.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = :sid AND s.tenant_id = :tid
        LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($profile) {
        echo "[SUCCESS] 'view' query executed. Batch: {$profile['batch_name']}, Course: {$profile['course_name']}\n";
    } else {
        echo "[FAILED] 'view' query returned no results.\n";
    }
    
    // 2. Test 'academic_history' action query logic
    $stmt = $db->prepare("
        SELECT 
            e.*, b.name as batch_name, c.name as course_name,
            COALESCE(CONCAT(c.duration_months, ' Months'), CONCAT(c.duration_weeks, ' Weeks'), 'N/A') as duration,
            c.fee as course_fee
        FROM enrollments e
        JOIN batches b ON e.batch_id = b.id
        JOIN courses c ON b.course_id = c.id
        WHERE e.student_id = :sid AND e.tenant_id = :tid
        ORDER BY e.created_at DESC
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "[SUCCESS] 'academic_history' query executed. Found " . count($history) . " records.\n";

    // 3. Test 'course' action query logic
    $stmt = $db->prepare("
        SELECT s.roll_no, b.name as batch_name, b.start_date as batch_start_date, 
               b.end_date as batch_end_date, b.status as batch_status,
               c.name as course_name, 
               COALESCE(CONCAT(c.duration_months, ' Months'), CONCAT(c.duration_weeks, ' Weeks'), 'N/A') as duration,
               c.fee as course_fee,
               c.description as course_description
        FROM students s
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
        LEFT JOIN batches b ON e.batch_id = b.id
        LEFT JOIN courses c ON b.course_id = c.id
        WHERE s.id = :sid AND s.tenant_id = :tid
        LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($course) {
        echo "[SUCCESS] 'course' query executed. Status: {$course['batch_status']}\n";
    } else {
        echo "[FAILED] 'course' query returned no results.\n";
    }

} catch (Exception $e) {
    echo "[CRITICAL ERROR] " . $e->getMessage() . "\n";
}
