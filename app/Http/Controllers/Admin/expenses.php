<?php
/**
 * Admin Expenses API
 * Route: /api/admin/expenses
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// error_log("Checking expenses.view");
if (!isLoggedIn() || !hasPermission('expenses.view')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenant_id = $_SESSION['userData']['tenant_id'] ?? null;
$user_id = $_SESSION['userData']['id'] ?? null;

if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();

    switch ($action) {
        case 'list':
            $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            $status = $_GET['status'] ?? null;
            $payment_method = $_GET['payment_method'] ?? null;

            $where = ["e.tenant_id = :tid", "e.deleted_at IS NULL"];
            $params = [':tid' => $tenant_id];

            if ($category_id > 0) {
                $where[] = "e.expense_category_id = :cid";
                $params[':cid'] = $category_id;
            }
            if ($start_date) {
                $where[] = "e.date_ad >= :start";
                $params[':start'] = $start_date;
            }
            if ($end_date) {
                $where[] = "e.date_ad <= :end";
                $params[':end'] = $end_date;
            }
            if ($status) {
                $where[] = "e.status = :status";
                $params[':status'] = $status;
            }
            if ($payment_method) {
                $where[] = "e.payment_method = :pm";
                $params[':pm'] = $payment_method;
            }

            $whereClause = implode(" AND ", $where);
            $sql = "
                SELECT 
                    e.*, 
                    c.name as category_name, c.icon as category_icon, c.color as category_color,
                    u.name as approved_by_name
                FROM expenses e
                LEFT JOIN expense_categories c ON e.expense_category_id = c.id
                LEFT JOIN users u ON e.approved_by = u.id
                WHERE $whereClause
                ORDER BY e.date_ad DESC, e.created_at DESC
                LIMIT 500
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'expenses' => $expenses]);
            break;

        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid Method");

            $id = intval($_POST['id'] ?? 0);
            $category_id = intval($_POST['expense_category_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $date_ad = $_POST['date_ad'] ?? date('Y-m-d');
            $date_bs = $_POST['date_bs'] ?? '';
            $description = trim($_POST['description'] ?? '');
            $payment_method = $_POST['payment_method'] ?? 'cash';
            $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
            
            // Payment method details
            $transaction_id = trim($_POST['transaction_id'] ?? '');
            $bank_name = trim($_POST['bank_name'] ?? '');
            $reference_number = trim($_POST['reference_number'] ?? '');
            $cheque_number = trim($_POST['cheque_number'] ?? '');
            $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
            $cheque_status = $_POST['cheque_status'] ?? 'pending';
            
            if ($category_id === 0) throw new Exception("Category is required");
            if ($amount <= 0) throw new Exception("Amount must be greater than zero");
            if (empty($date_bs)) {
                $date_bs = \App\Helpers\DateUtils::adToBs($date_ad);
            }

            $receipt_path = $_POST['existing_receipt'] ?? null;
            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = APP_ROOT . '/storage/uploads/expenses/' . $tenant_id . '/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['receipt']['name']));
                $targetFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetFilePath)) {
                    $receipt_path = 'storage/uploads/expenses/' . $tenant_id . '/' . $fileName;
                }
            }

            if ($id > 0) {
                $sql = "
                    UPDATE expenses SET 
                        expense_category_id = :cid, amount = :amount, date_ad = :dad, date_bs = :dbs, 
                        description = :desc, payment_method = :pm, receipt_path = :rp, is_recurring = :irr,
                        transaction_id = :tid, bank_name = :bn, reference_number = :rn, 
                        cheque_number = :cn, cheque_date = :cd, cheque_status = :cs,
                        updated_at = NOW()
                    WHERE id = :id AND tenant_id = :tenant_id
                ";
                $params = [
                    'cid' => $category_id, 'amount' => $amount, 'dad' => $date_ad, 'dbs' => $date_bs,
                    'desc' => $description, 'pm' => $payment_method, 'rp' => $receipt_path, 'irr' => $is_recurring,
                    'tid' => $transaction_id, 'bn' => $bank_name, 'rn' => $reference_number,
                    'cn' => $cheque_number, 'cd' => $cheque_date, 'cs' => $cheque_status,
                    'id' => $id, 'tenant_id' => $tenant_id
                ];
                $message = "Expense updated successfully";
            } else {
                $sql = "
                    INSERT INTO expenses (
                        tenant_id, expense_category_id, amount, date_ad, date_bs, description, 
                        payment_method, receipt_path, is_recurring, transaction_id, bank_name, 
                        reference_number, cheque_number, cheque_date, cheque_status, status, 
                        created_by, created_at, updated_at
                    ) VALUES (
                        :tenant_id, :cid, :amount, :dad, :dbs, :desc, :pm, :rp, :irr, 
                        :tid, :bn, :rn, :cn, :cd, :cs, 'approved', :created_by, NOW(), NOW()
                    )
                ";
                $params = [
                    'tenant_id' => $tenant_id, 'cid' => $category_id, 'amount' => $amount, 'dad' => $date_ad, 'dbs' => $date_bs,
                    'desc' => $description, 'pm' => $payment_method, 'rp' => $receipt_path, 'irr' => $is_recurring,
                    'tid' => $transaction_id, 'bn' => $bank_name, 'rn' => $reference_number,
                    'cn' => $cheque_number, 'cd' => $cheque_date, 'cs' => $cheque_status,
                    'created_by' => $user_id
                ];
                $message = "Expense recorded and approved successfully";
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if ($id === 0) $id = $db->lastInsertId();

            echo json_encode(['success' => true, 'message' => $message, 'id' => $id]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid Method");
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception("ID missing");

            $stmt = $db->prepare("UPDATE expenses SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenant_id]);

            echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
            break;

        case 'approve':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid Method");
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception("ID missing");

            $stmt = $db->prepare("UPDATE expenses SET status = 'approved', approved_by = :uid, updated_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenant_id, 'uid' => $user_id]);

            echo json_encode(['success' => true, 'message' => 'Expense approved successfully']);
            break;

        default:
            throw new Exception("Invalid Action");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
