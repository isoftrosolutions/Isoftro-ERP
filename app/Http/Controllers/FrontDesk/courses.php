<?php
/**
 * Courses API Controller
 * Handles fetching courses for the current tenant
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// RBAC check
if (!in_array($role, ['instituteadmin', 'frontdesk', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Restrict non-GET methods to admins
if ($method !== 'GET' && !in_array($role, ['instituteadmin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden: Only administrators can create or modify courses.']);
    exit;
}

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        // List courses
        $query = "SELECT c.*, 
                  (SELECT COUNT(*) FROM batches b WHERE b.course_id = c.id AND b.deleted_at IS NULL) as total_batches,
                  (SELECT COUNT(*) FROM students s JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' JOIN batches b ON e.batch_id = b.id WHERE b.course_id = c.id AND s.deleted_at IS NULL) as total_students
                  FROM courses c 
                  WHERE c.tenant_id = :tid AND c.deleted_at IS NULL AND c.is_active = 1 AND c.status = 'active'";
        // ISSUE-C2 FIX: Filter by both is_active AND status for consistency
        
        $params = ['tid' => $tenantId];

        if (!empty($_GET['id'])) {
            $query .= " AND c.id = :id";
            $params['id'] = $_GET['id'];
        }

        if (!empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $query .= " AND (c.name LIKE :search OR c.code LIKE :search)";
            $params['search'] = $search;
        }

        $query .= " ORDER BY c.name ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $courses = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $courses]);
    }

    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $name = $input['name'] ?? '';
        $code = $input['code'] ?? '';
        $category = $input['category'] ?? 'general';
        $fee = floatval($input['fee'] ?? 0);

        if (empty($name) || empty($code)) {
            throw new Exception("Course Name and Code are required");
        }

        $stmt = $db->prepare("
            INSERT INTO courses (tenant_id, code, name, category, description, duration_weeks, seats, fee, is_active) 
            VALUES (:tid, :code, :name, :category, :desc, :dur, :seats, :fee, 1)
        ");

        $stmt->execute([
            'tid' => $tenantId,
            'code' => $code,
            'name' => $name,
            'category' => $category,
            'desc' => $input['description'] ?? null,
            'dur' => $input['duration_weeks'] ?? null,
            'seats' => $input['seats'] ?? null,
            'fee' => $fee
        ]);

        $courseId = $db->lastInsertId();

        // ASSOCIATE WITH FEE TABLE: Create a default fee item for this course
        if ($fee > 0) {
            $stmt = $db->prepare("
                INSERT INTO fee_items (tenant_id, course_id, name, type, amount, installments, late_fine_per_day, is_active) 
                VALUES (:tid, :course_id, :name, 'admission', :amount, 1, 0, 1)
            ");
            $stmt->execute([
                'tid' => $tenantId,
                'course_id' => $courseId,
                'name' => 'Tuition Fee - ' . $name,
                'amount' => $fee
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Course created successfully', 'id' => $courseId]);
    }

    else if ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Course ID is required");

        // Verify ownership
        $stmt = $db->prepare("SELECT id, name FROM courses WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        $courseData = $stmt->fetch();
        if (!$courseData) throw new Exception("Course not found");

        $fields = [];
        $params = ['id' => $id, 'tid' => $tenantId];
        $allowed = ['code', 'name', 'category', 'description', 'duration_weeks', 'seats', 'is_active', 'fee'];

        foreach ($allowed as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = :$f";
                $params[$f] = $input[$f];
            }
        }

        if (empty($fields)) throw new Exception("No fields to update");

        $stmt = $db->prepare("UPDATE courses SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tid");
        $stmt->execute($params);

        // SYNC WITH FEE TABLE if fee was updated
        if (isset($input['fee'])) {
            $newFee = floatval($input['fee']);
            // Check if a fee item already exists for this course
            $stmt = $db->prepare("SELECT id FROM fee_items WHERE course_id = :cid AND tenant_id = :tid AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['cid' => $id, 'tid' => $tenantId]);
            $feeItem = $stmt->fetch();

            if ($feeItem) {
                $stmt = $db->prepare("UPDATE fee_items SET amount = :amount WHERE id = :id");
                $stmt->execute(['amount' => $newFee, 'id' => $feeItem['id']]);
            } else if ($newFee > 0) {
                // Create one if it doesn't exist but has a value now
                $stmt = $db->prepare("
                    INSERT INTO fee_items (tenant_id, course_id, name, type, amount, installments, late_fine_per_day, is_active) 
                    VALUES (:tid, :course_id, :name, 'admission', :amount, 1, 0, 1)
                ");
                $stmt->execute([
                    'tid' => $tenantId,
                    'course_id' => $id,
                    'name' => 'Tuition Fee - ' . ($input['name'] ?? $courseData['name']),
                    'amount' => $newFee
                ]);
            }
            
            // Cascade updated course fee to existing students' fee summaries
            $stmtCascade = $db->prepare("
                UPDATE student_fee_summary sfs 
                JOIN enrollments e ON sfs.enrollment_id = e.id
                JOIN batches b ON e.batch_id = b.id
                SET 
                    sfs.total_fee = :newFee1,
                    sfs.due_amount = CASE WHEN (:newFee2 - sfs.paid_amount) < 0 THEN 0 ELSE (:newFee3 - sfs.paid_amount) END,
                    sfs.fee_status = CASE 
                        WHEN (:newFee4 - sfs.paid_amount) <= 0 THEN 'paid'
                        WHEN sfs.paid_amount > 0 THEN 'partial'
                        ELSE 'unpaid'
                    END
                WHERE b.course_id = :cid AND sfs.tenant_id = :tid AND e.status = 'active'
            ");
            $stmtCascade->execute([
                'newFee1' => $newFee, 
                'newFee2' => $newFee, 
                'newFee3' => $newFee, 
                'newFee4' => $newFee, 
                'cid' => $id, 
                'tid' => $tenantId
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Course updated successfully']);
    }

    else if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_GET;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Course ID is required");

        // Check if there are active batches
        $stmt = $db->prepare("SELECT COUNT(*) FROM batches WHERE course_id = :cid AND deleted_at IS NULL");
        $stmt->execute(['cid' => $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete course with active batches");
        }

        $stmt = $db->prepare("UPDATE courses SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        
        // Also soft delete associated fee items
        $stmt = $db->prepare("UPDATE fee_items SET deleted_at = NOW() WHERE course_id = :cid AND tenant_id = :tid");
        $stmt->execute(['cid' => $id, 'tid' => $tenantId]);

        echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
    }
} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }

