<?php
/**
 * Appointments Controller
 * Uses 'inquiries' table with inquiry_type = 'appointment'
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$role = $user['role'] ?? '';

require_once __DIR__ . '/../../Middleware/FrontDeskMiddleware.php';
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$role = $auth['role'];

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        $date = $_GET['date'] ?? null;

        $query = "SELECT * FROM inquiries WHERE tenant_id = :tid AND inquiry_type = 'appointment' AND deleted_at IS NULL";
        $params = ['tid' => $tenantId];

        if ($id) {
            $query .= " AND id = :id";
            $params['id'] = $id;
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
        } else {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            if ($date) {
                $query .= " AND appointment_date = :date";
                $params['date'] = $date;
            }

            // Count total
            $countQuery = str_replace("SELECT *", "SELECT COUNT(*)", $query);
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute($params);
            $totalRecords = (int)$countStmt->fetchColumn();

            $query .= " ORDER BY appointment_date ASC, appointment_time ASC LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;

            $stmt = $db->prepare($query);
            foreach ($params as $key => $val) {
                $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(":{$key}", $val, $type);
            }
            $stmt->execute();
            $appointments = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => $appointments,
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalRecords / $limit)
            ]);
        }
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $name = $input['full_name'] ?? '';
        $phone = $input['phone'] ?? '';
        $date = $input['appointment_date'] ?? '';
        $time = $input['appointment_time'] ?? '';
        $notes = $input['notes'] ?? '';

        if (empty($name) || empty($phone) || empty($date)) {
            throw new Exception("Name, phone, and date are required.");
        }

        $stmt = $db->prepare("
            INSERT INTO inquiries (tenant_id, inquiry_type, full_name, phone, appointment_date, appointment_time, notes, status, created_at, updated_at)
            VALUES (:tid, 'appointment', :name, :phone, :date, :time, :notes, 'pending', NOW(), NOW())
        ");
        $stmt->execute([
            'tid' => $tenantId,
            'name' => $name,
            'phone' => $phone,
            'date' => $date,
            'time' => $time,
            'notes' => $notes
        ]);

        echo json_encode(['success' => true, 'message' => 'Appointment scheduled successfully', 'id' => $db->lastInsertId()]);
    } 
    elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        $action = $input['action'] ?? '';

        if (!$id) throw new Exception("Appointment ID required.");

        if ($action === 'complete') {
            $stmt = $db->prepare("UPDATE inquiries SET status = 'closed', updated_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'appointment'");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'message' => 'Appointment marked as completed']);
        } else {
            $fields = [];
            $params = ['id' => $id, 'tid' => $tenantId];
            if (isset($input['full_name'])) { $fields[] = "full_name = :name"; $params['name'] = $input['full_name']; }
            if (isset($input['phone'])) { $fields[] = "phone = :phone"; $params['phone'] = $input['phone']; }
            if (isset($input['appointment_date'])) { $fields[] = "appointment_date = :date"; $params['date'] = $input['appointment_date']; }
            if (isset($input['appointment_time'])) { $fields[] = "appointment_time = :time"; $params['time'] = $input['appointment_time']; }
            if (isset($input['notes'])) { $fields[] = "notes = :notes"; $params['notes'] = $input['notes']; }
            if (isset($input['status'])) { $fields[] = "status = :status"; $params['status'] = $input['status']; }
            
            if (empty($fields)) throw new Exception("Nothing to update.");
            
            $stmt = $db->prepare("UPDATE inquiries SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'appointment'");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Appointment updated']);
        }
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");

        $stmt = $db->prepare("UPDATE inquiries SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'appointment'");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled/archived']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
