<?php
/**
 * Batches API Controller
 * Handles fetching batches for the current tenant
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

// Try to load DateUtils if available
function convertToBs($adDate) {
    if (empty($adDate)) return null;
    try {
        if (class_exists('App\Helpers\DateUtils')) {
            $result = \App\Helpers\DateUtils::adToBs($adDate);
            return !empty($result) ? $result : null;
        }
    } catch (\Throwable $e) {
        // Catches both Exception and Error (TypeError, etc.)
    }
    return null;
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
if (!in_array($role, ['instituteadmin', 'frontdesk', 'superadmin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Restrict non-GET methods to admins
if ($method !== 'GET' && !in_array($role, ['instituteadmin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden: Only administrators can create or modify batches.']);
    exit;
}

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        // List batches
        $query = "SELECT b.*, c.name as course_name,
                  (SELECT COUNT(*) FROM enrollments e JOIN students s ON e.student_id = s.id WHERE e.batch_id = b.id AND e.status = 'active' AND s.deleted_at IS NULL) as total_students
                  FROM batches b
                  JOIN courses c ON b.course_id = c.id
                    AND c.deleted_at IS NULL AND c.is_active = 1
                  WHERE b.tenant_id = :tid AND b.deleted_at IS NULL";
        // ISSUE-B2 FIX: JOIN now guards against soft-deleted / inactive parent courses
        
        $params = ['tid' => $tenantId];

        if (!empty($_GET['id'])) {
            $query .= " AND b.id = :id";
            $params['id'] = $_GET['id'];
        }

        if (!empty($_GET['course_id'])) {
            $query .= " AND b.course_id = :course_id";
            $params['course_id'] = $_GET['course_id'];
        }

        // ISSUE-B1 FIX: 'open' is a virtual status meaning active OR upcoming (for admission forms)
        if (!empty($_GET['status'])) {
            $status = $_GET['status'];
            if ($status === 'open') {
                $query .= " AND b.status IN ('active','upcoming')";
            } else {
                $query .= " AND b.status = :status";
                $params['status'] = $status;
            }
        }

        $query .= " ORDER BY b.start_date DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $batches = $stmt->fetchAll();

        // Add Nepali dates for display
        foreach ($batches as &$b) {
            $b['start_date_bs'] = convertToBs($b['start_date']);
            $b['end_date_bs'] = convertToBs($b['end_date']);
        }

        echo json_encode(['success' => true, 'data' => $batches]);
    }

    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $courseId = $input['course_id'] ?? null;
        $name = $input['name'] ?? '';
        $shift = $input['shift'] ?? 'morning';
        
        // Date handling
        $startDate = $input['start_date'] ?? null;
        $startDateBs = $input['start_date_bs'] ?? null;
        $endDate = $input['end_date'] ?? null;
        $endDateBs = $input['end_date_bs'] ?? null;

        // Convert BS to AD if needed
        if (empty($startDate) && !empty($startDateBs)) {
            if (class_exists('App\Helpers\DateUtils')) {
                try {
                    $startDate = App\Helpers\DateUtils::bsToAd($startDateBs);
                } catch (Exception $e) {}
            }
        }
        if (empty($endDate) && !empty($endDateBs)) {
            if (class_exists('App\Helpers\DateUtils')) {
                try {
                    $endDate = App\Helpers\DateUtils::bsToAd($endDateBs);
                } catch (Exception $e) {}
            }
        }

        if (empty($startDate)) $startDate = date('Y-m-d');

        // Handle end_date - allow null if not provided (optional field)
        if (empty($endDate)) {
            $endDate = null;
        }

        if (!$courseId || !$name) {
            throw new Exception("Course and Batch Name are required");
        }

        $stmt = $db->prepare("
            INSERT INTO batches (tenant_id, course_id, name, shift, start_date, end_date, max_strength, room, status) 
            VALUES (:tid, :cid, :name, :shift, :sdate, :edate, :max, :room, :status)
        ");

        $stmt->execute([
            'tid' => $tenantId,
            'cid' => $courseId,
            'name' => $name,
            'shift' => $shift,
            'sdate' => $startDate,
            'edate' => $endDate,
            'max' => $input['max_strength'] ?? 40,
            'room' => $input['room'] ?? null,
            'status' => $input['status'] ?? 'active'
        ]);

        echo json_encode(['success' => true, 'message' => 'Batch created successfully', 'id' => $db->lastInsertId()]);
    }

    else if ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Batch ID is required");

        // Verify ownership
        $stmt = $db->prepare("SELECT id FROM batches WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        if (!$stmt->fetch()) throw new Exception("Batch not found");

        $fields = [];
        $params = ['id' => $id, 'tid' => $tenantId];
        $allowed = ['course_id', 'name', 'shift', 'start_date', 'end_date', 'max_strength', 'room', 'status'];

        foreach ($allowed as $f) {
            if (isset($input[$f])) {
                $val = $input[$f];
                
                // Convert empty strings to null for date fields
                if (in_array($f, ['start_date', 'end_date']) && $val === '') {
                    $val = null;
                }
                
                // Special handling for BS date updates
                if ($f === 'start_date' && empty($val) && !empty($input['start_date_bs'])) {
                    if (class_exists('App\Helpers\DateUtils')) {
                        try {
                            $val = App\Helpers\DateUtils::bsToAd($input['start_date_bs']);
                        } catch (Exception $e) {}
                    }
                }
                if ($f === 'end_date' && empty($val) && !empty($input['end_date_bs'])) {
                    if (class_exists('App\Helpers\DateUtils')) {
                        try {
                            $val = App\Helpers\DateUtils::bsToAd($input['end_date_bs']);
                        } catch (Exception $e) {}
                    }
                }

                $fields[] = "$f = :$f";
                $params[$f] = $val;
            }
        }

        if (empty($fields)) throw new Exception("No fields to update");

        $stmt = $db->prepare("UPDATE batches SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tid");
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Batch updated successfully']);
    }

    else if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_GET;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Batch ID is required");

        $stmt = $db->prepare("UPDATE batches SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);

        echo json_encode(['success' => true, 'message' => 'Batch deleted successfully']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
