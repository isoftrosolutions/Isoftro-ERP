<?php
/**
 * Inquiries API Controller
 * Handles fetching and managing inquiries for the current tenant
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// CSRF and role check via Middleware
require_once __DIR__ . '/../../Middleware/FrontDeskMiddleware.php';
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$userRole = $auth['role'];
$userId = $auth['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        $id = !empty($_GET['id']) ? (int)$_GET['id'] : null;

        if ($id) {
            // Full details for single inquiry
            $query = "SELECT i.*, c.name as course_name
                      FROM inquiries i
                      LEFT JOIN courses c ON i.course_id = c.id
                      WHERE i.id = :id AND i.tenant_id = :tid AND i.deleted_at IS NULL";
            $params = ['id' => $id, 'tid' => $tenantId];
        } else {
            // Pagination settings
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Type filter
            $typeFilter = $_GET['type'] ?? 'inquiry';
            
            // Base filters for both count and data
            $filterQuery = " WHERE i.tenant_id = :tid AND i.deleted_at IS NULL AND i.inquiry_type = :type";
            $filterParams = ['tid' => $tenantId, 'type' => $typeFilter];

            if (!empty($_GET['search'])) {
                $search = '%' . $_GET['search'] . '%';
                $filterQuery .= " AND (i.full_name LIKE :search OR i.phone LIKE :search OR i.email LIKE :search)";
                $filterParams['search'] = $search;
            }

            if (!empty($_GET['status'])) {
                $filterQuery .= " AND i.status = :status";
                $filterParams['status'] = $_GET['status'];
            }

            // 1. Count total records
            $countStmt = $db->prepare("SELECT COUNT(*) FROM inquiries i " . $filterQuery);
            $countStmt->execute($filterParams);
            $totalRecords = (int)$countStmt->fetchColumn();

            // 2. Fetch paginated data
            $query = "SELECT i.id, i.full_name, i.phone, i.email, i.source, i.status,
                             i.inquiry_type, i.notes, i.created_at, i.updated_at,
                             c.name as course_name
                      FROM inquiries i
                      LEFT JOIN courses c ON i.course_id = c.id
                      " . $filterQuery . "
                      ORDER BY i.created_at DESC LIMIT :limit OFFSET :offset";
            
            $filterParams['limit'] = $limit;
            $filterParams['offset'] = $offset;

            $stmt = $db->prepare($query);
            foreach ($filterParams as $key => $val) {
                $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(":{$key}", $val, $type);
            }
            $stmt->execute();
        }

        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($id && empty($inquiries)) {
            echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
        } else {
            $response = ['success' => true, 'data' => $id ? $inquiries[0] : $inquiries];
            if (!$id) {
                $response['total'] = $totalRecords;
                $response['page'] = $page;
                $response['limit'] = $limit;
                $response['total_pages'] = ceil($totalRecords / $limit);
            }
            echo json_encode($response);
        }
    }
    
    if ($method === 'POST' || $method === 'PUT') {
        // Create or update inquiry / add follow-up
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        // ── Status update action ────────────────────────────────────────
        if (!empty($input['action']) && $input['action'] === 'update_status') {
            $inquiryId = (int)($input['inquiry_id'] ?? 0);
            $newStatus = trim($input['status'] ?? '');
            if (!$inquiryId || !$newStatus) {
                echo json_encode(['success' => false, 'message' => 'Inquiry ID and status are required.']);
                exit;
            }
            $stmt = $db->prepare("UPDATE inquiries SET status = :status, updated_at = NOW() WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
            $stmt->execute(['status' => $newStatus, 'id' => $inquiryId, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
            exit;
        }

        // ── Follow-up action ────────────────────────────────────────
        if (!empty($input['action']) && $input['action'] === 'followup') {
            $inquiryId = (int)($input['inquiry_id'] ?? 0);
            $remarks   = trim($input['remarks'] ?? '');
            if (!$inquiryId || !$remarks) {
                echo json_encode(['success' => false, 'message' => 'Inquiry ID and remarks are required.']);
                exit;
            }
            // Verify inquiry belongs to this tenant
            $stmtChk = $db->prepare("SELECT id FROM inquiries WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
            $stmtChk->execute(['id' => $inquiryId, 'tid' => $tenantId]);
            if (!$stmtChk->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Inquiry not found.']);
                exit;
            }
            $stmtFu = $db->prepare(
                "INSERT INTO inquiry_followups (inquiry_id, user_id, remarks, next_followup_date, created_at)
                 VALUES (:iid, :uid, :remarks, :next_date, NOW())"
            );
            $stmtFu->execute([
                'iid'       => $inquiryId,
                'uid'       => $userId,
                'remarks'   => $remarks,
                'next_date' => $input['next_followup_date'] ?? null,
            ]);
            // Update inquiry status to follow_up if it was pending
            $db->prepare("UPDATE inquiries SET status = 'follow_up', updated_at = NOW() WHERE id = :id AND status = 'pending'")
               ->execute(['id' => $inquiryId]);
            echo json_encode(['success' => true, 'message' => 'Follow-up recorded successfully.']);
            exit;
        }

        // Validate required fields for create/update
        if (empty($input['full_name'])) {
            echo json_encode(['success' => false, 'message' => 'Full name is required']);
            exit;
        }
        
        if (empty($input['phone'])) {
            echo json_encode(['success' => false, 'message' => 'Phone number is required']);
            exit;
        }
        
        if (empty($input['course_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please select a course']);
            exit;
        }
        
        if (empty($input['source'])) {
            echo json_encode(['success' => false, 'message' => 'Please select a source']);
            exit;
        }
        
        // Check if updating or creating
        $inquiryId = $input['id'] ?? null;
        
        // Get database columns to check if optional fields exist
        $stmt = $db->query("SHOW COLUMNS FROM inquiries LIKE 'alt_phone'");
        $hasAltPhone = $stmt->fetch() !== false;
        
        $stmt = $db->query("SHOW COLUMNS FROM inquiries LIKE 'address'");
        $hasAddress = $stmt->fetch() !== false;
        
        if ($inquiryId) {
            // Update existing inquiry
            $query = "UPDATE inquiries SET 
                full_name = :name,
                phone = :phone,
                email = :email,
                course_id = :course_id,
                source = :source,
                status = :status,
                notes = :notes,
                updated_at = NOW()";
            
            $params = [
                'id' => $inquiryId,
                'name' => $input['full_name'],
                'phone' => $input['phone'],
                'email' => $input['email'] ?? null,
                'course_id' => $input['course_id'],
                'source' => $input['source'],
                'status' => $input['status'] ?? 'pending',
                'notes' => $input['notes'] ?? null,
                'tid' => $tenantId
            ];
            
            // Add optional fields if they exist in the table
            if ($hasAltPhone) {
                $query .= ", alt_phone = :alt_phone";
                $params['alt_phone'] = $input['alt_phone'] ?? null;
            }
            if ($hasAddress) {
                $query .= ", address = :address";
                $params['address'] = $input['address'] ?? null;
            }
            
            $query .= " WHERE id = :id AND tenant_id = :tid";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Inquiry updated successfully', 'id' => $inquiryId]);
        } else {
            // Build dynamic query based on available columns
            $inquiryType = in_array($input['inquiry_type'] ?? 'inquiry', ['inquiry','visitor','appointment','call_log','complaint'])
                ? ($input['inquiry_type'] ?? 'inquiry') : 'inquiry';
            $query = "INSERT INTO inquiries (
                tenant_id, inquiry_type, full_name, phone, email, course_id, source, status, notes, created_at, updated_at
            ) VALUES (
                :tid, :inquiry_type, :name, :phone, :email, :course_id, :source, :status, :notes, NOW(), NOW()
            )";
            
            $params = [
                'tid'          => $tenantId,
                'inquiry_type' => $inquiryType,
                'name'         => $input['full_name'],
                'phone'        => $input['phone'],
                'email'        => $input['email'] ?? null,
                'course_id'    => !empty($input['course_id']) ? (int)$input['course_id'] : null,
                'source'       => $input['source'] ?? 'walk_in',
                'status'       => $input['status'] ?? 'pending',
                'notes'        => $input['notes'] ?? null,
            ];
            
            // Add optional fields if they exist in the table
            if ($hasAltPhone) {
                $query = str_replace('course_id, source', 'course_id, alt_phone, source', $query);
                $query = str_replace(':course_id, :source', ':course_id, :alt_phone, :source', $query);
                $params['alt_phone'] = $input['alt_phone'] ?? null;
            }
            if ($hasAddress) {
                $query = str_replace('status, notes', 'status, address, notes', $query);
                $query = str_replace(':status, :notes', ':status, :address, :notes', $query);
                $params['address'] = $input['address'] ?? null;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            $newId = $db->lastInsertId();
            echo json_encode(['success' => true, 'message' => 'Inquiry created successfully', 'id' => $newId]);
        }
    }
    
    if ($method === 'DELETE') {
        // Soft-delete inquiry (preserves data for reports)
        $input = json_decode(file_get_contents('php://input'), true);
        $inquiryId = !empty($input['id']) ? (int)$input['id'] : (!empty($_GET['id']) ? (int)$_GET['id'] : null);

        if (!$inquiryId) {
            echo json_encode(['success' => false, 'message' => 'Inquiry ID is required']);
            exit;
        }

        $stmt = $db->prepare("UPDATE inquiries SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['id' => $inquiryId, 'tid' => $tenantId]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Inquiry not found or already deleted.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Inquiry archived successfully.']);
        }
    }
} catch (PDOException $e) {
    error_log('Inquiries Controller DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
} catch (Exception $e) {
    error_log('Inquiries Controller Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}
