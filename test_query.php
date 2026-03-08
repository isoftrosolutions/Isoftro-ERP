<?php
require_once __DIR__ . '/config/config.php';
$db = getDBConnection();

// Find a tenant with students
$stmt = $db->query("SELECT tenant_id, COUNT(*) as count FROM students GROUP BY tenant_id HAVING count > 0 LIMIT 1");
$row = $stmt->fetch();
if (!$row) {
    die("No students found in any tenant.\n");
}
$tenantId = $row['tenant_id'];
echo "Testing with tenant ID: $tenantId\n";

$perPage = 10;
$page = 1;
$offset = 0;

$whereSql = "WHERE s.tenant_id = :tid AND s.deleted_at IS NULL";
$params = ['tid' => $tenantId];

try {
    $query = "SELECT s.*, s.registration_mode, s.registration_status, s.admission_date,
                     b.name as batch_name, b.course_id as course_id, c.name as course_name,
                     COALESCE(sfs.fee_status, 'no_fees') as fee_status,
                     COALESCE(sfs.total_fee, 0) as total_fee,
                     COALESCE(sfs.paid_amount, 0) as paid_amount,
                     COALESCE(sfs.due_amount, 0) as due_amount
              FROM students s 
              LEFT JOIN batches b ON s.batch_id = b.id 
              LEFT JOIN courses c ON b.course_id = c.id
              LEFT JOIN student_fee_summary sfs ON s.id = sfs.student_id
              $whereSql
              ORDER BY s.created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    echo "Executing data query...\n";
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Success! Fetched " . count($students) . " students.\n";

    if (count($students) > 0) {
        foreach ($students as $student) {
            $sid = $student['id'];
            echo "Testing fee summary query for student $sid...\n";
            $feeStmt = $db->prepare("
                SELECT 
                    sfs.fee_status,
                    COALESCE(sfs.total_fee, 0) as total_due,
                    COALESCE(sfs.paid_amount, 0) as total_paid,
                    COALESCE(sfs.due_amount, 0) as pending_amount
                FROM student_fee_summary sfs
                JOIN enrollments e ON sfs.enrollment_id = e.id
                WHERE sfs.student_id = :sid AND sfs.tenant_id = :tid AND e.status = 'active'
                LIMIT 1
            ");
            $feeStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
            $feeData = $feeStmt->fetch(PDO::FETCH_ASSOC);
            echo "  Result: " . ($feeData ? "Found" : "Not Found") . "\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "STACK: " . $e->getTraceAsString() . "\n";
}
