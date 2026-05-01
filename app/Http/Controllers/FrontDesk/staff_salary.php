<?php
/**
 * Staff Salary API Controller
 * Handles salary management for teachers and other staff members
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');
ob_start();

// Ensure user is logged in and has appropriate role (Institute Admin)
if (!isLoggedIn() || ($_SESSION['userData']['role'] ?? '') !== 'instituteadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();

try {
    if ($method === 'GET') {
        $userId = $_GET['user_id'] ?? null;
        $role = $_GET['role'] ?? null;
        $month = $_GET['month'] ?? null;
        $year = $_GET['year'] ?? null;

        $query = "
            SELECT ss.*, u.name as staff_name, u.role as staff_role
            FROM staff_salaries ss
            JOIN users u ON ss.user_id = u.id
            WHERE ss.tenant_id = :tid
        ";
        $params = ['tid' => $tenantId];

        if ($userId) {
            $query .= " AND ss.user_id = :uid";
            $params['uid'] = $userId;
        }
        if ($role) {
            $query .= " AND u.role = :role";
            $params['role'] = $role;
        }
        if ($month) {
            $query .= " AND ss.month = :month";
            $params['month'] = $month;
        }
        if ($year) {
            $query .= " AND ss.year = :year";
            $params['year'] = $year;
        }

        $query .= " ORDER BY ss.year DESC, ss.month DESC, ss.payment_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $salaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $salaries]);

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $userId = $input['user_id'] ?? null;
        $amount = $input['amount'] ?? null;
        $month = $input['month'] ?? null;
        $year = $input['year'] ?? null;
        $paymentDate = $input['payment_date'] ?? date('Y-m-d');
        $status = $input['status'] ?? 'paid';
        $method = $input['payment_method'] ?? 'cash';
        $transactionId = $input['transaction_id'] ?? null;
        $remarks = $input['remarks'] ?? null;

        if (!$userId || !$amount || !$month || !$year) {
            throw new Exception("Missing required fields: user_id, amount, month, year");
        }

        // Verify user belongs to this tenant
        $stmt = $db->prepare("SELECT id FROM users WHERE id = :uid AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        if (!$stmt->fetch()) {
            throw new Exception("Staff member not found or access denied");
        }

        $stmt = $db->prepare("
            INSERT INTO staff_salaries 
            (tenant_id, user_id, amount, month, year, payment_date, status, payment_method, transaction_id, remarks)
            VALUES (:tid, :uid, :amount, :month, :year, :pdate, :status, :pmethod, :txid, :remarks)
        ");
        $stmt->execute([
            'tid' => $tenantId,
            'uid' => $userId,
            'amount' => $amount,
            'month' => $month,
            'year' => $year,
            'pdate' => $paymentDate,
            'status' => $status,
            'pmethod' => $method,
            'txid' => $transactionId,
            'remarks' => $remarks
        ]);

        echo json_encode(['success' => true, 'message' => 'Salary record added successfully', 'id' => $db->lastInsertId()]);

    } elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Salary record ID is required");

        // Verify ownership
        $stmt = $db->prepare("SELECT id FROM staff_salaries WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        if (!$stmt->fetch()) throw new Exception("Record not found or access denied");

        $fields = [];
        $params = ['id' => $id, 'tid' => $tenantId];

        $updatable = ['amount', 'month', 'year', 'payment_date', 'status', 'payment_method', 'transaction_id', 'remarks'];
        foreach ($updatable as $f) {
            if (isset($input[$f])) {
                $fields[] = "`$f` = :$f";
                $params[$f] = $input[$f];
            }
        }

        if (empty($fields)) throw new Exception("No fields to update");

        $stmt = $db->prepare("UPDATE staff_salaries SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tid");
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Salary record updated successfully']);

    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_GET;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Salary record ID is required");

        $stmt = $db->prepare("DELETE FROM staff_salaries WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);

        echo json_encode(['success' => true, 'message' => 'Salary record deleted successfully']);
    }

} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
ob_end_flush();
exit;
