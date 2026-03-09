<?php
/**
 * Library Controller — Catalog, Issue/Return, Overdue Tracking, Stock Report
 * Implemented V3.1
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
if (!in_array($role, ['instituteadmin', 'frontdesk', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $spoofedMethod = strtoupper($_POST['_method']);
    if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
        $method = $spoofedMethod;
    }
}
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

try {
    $db = getDBConnection();

    // ----------------------------------------------------
    // Books Catalog CRUD
    // ----------------------------------------------------
    if ($action === 'list_books' && $method === 'GET') {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $offset = ($page - 1) * $perPage;
        
        $search = $_GET['search'] ?? '';
        $whereSql = "tenant_id = :tid AND deleted_at IS NULL";
        $params = [':tid' => $tenantId];
        
        if ($search) {
            $whereSql .= " AND (title LIKE :search OR author LIKE :search OR isbn LIKE :search OR category LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM library_books WHERE $whereSql");
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();
        
        $query = "SELECT * FROM library_books WHERE $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $books,
            'meta' => ['total' => $total, 'page' => $page, 'per_page' => $perPage]
        ]);
        exit;
    }

    if ($action === 'save_book' && in_array($method, ['POST', 'PUT'])) {
        $id = $input['id'] ?? null;
        $title = $input['title'] ?? '';
        $author = $input['author'] ?? '';
        $isbn = $input['isbn'] ?? '';
        $publisher = $input['publisher'] ?? '';
        $category = $input['category'] ?? '';
        $price = $input['price'] ?? 0.00;
        $rack_no = $input['rack_no'] ?? '';
        $quantity = (int)($input['quantity'] ?? 1);
        
        if (empty($title)) throw new Exception("Book title is required");
        
        if ($id) {
            // Adjust available stock based on quantity change
            $stmtQty = $db->prepare("SELECT quantity, available FROM library_books WHERE id = :id AND tenant_id = :tid FOR UPDATE");
            $stmtQty->execute([':id' => $id, ':tid' => $tenantId]);
            $current = $stmtQty->fetch();
            if (!$current) throw new Exception("Book not found");
            
            $diff = $quantity - $current['quantity'];
            $newAvailable = max(0, $current['available'] + $diff);
            
            $stmt = $db->prepare("UPDATE library_books SET title=?, author=?, isbn=?, publisher=?, category=?, price=?, rack_no=?, quantity=?, available=? WHERE id=? AND tenant_id=?");
            $stmt->execute([$title, $author, $isbn, $publisher, $category, $price, $rack_no, $quantity, $newAvailable, $id, $tenantId]);
            
            \App\Helpers\AuditLogger::log('UPDATE', 'library_books', $id, $current, $input);
            $msg = "Book updated successfully";
        } else {
            $stmt = $db->prepare("INSERT INTO library_books (tenant_id, title, author, isbn, publisher, category, price, rack_no, quantity, available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenantId, $title, $author, $isbn, $publisher, $category, $price, $rack_no, $quantity, $quantity]);
            $newId = $db->lastInsertId();
            
            \App\Helpers\AuditLogger::log('CREATE', 'library_books', $newId, null, $input);
            $msg = "Book added successfully";
        }
        
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    if ($action === 'delete_book' && $method === 'DELETE') {
        $id = $input['id'] ?? ( $_GET['id'] ?? null);
        if (!$id) throw new Exception("Book ID required");
        $stmt = $db->prepare("UPDATE library_books SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        
        \App\Helpers\AuditLogger::log('DELETE', 'library_books', $id);
        echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
        exit;
    }


    // ----------------------------------------------------
    // Issue & Return Logic
    // ----------------------------------------------------
    if ($action === 'list_issues' && $method === 'GET') {
        $status = $_GET['status'] ?? ''; // issued, returned, overdue, lost
        $search = $_GET['search'] ?? '';
        
        $whereSql = "li.tenant_id = :tid";
        $params = [':tid' => $tenantId];
        
        if ($status) {
            $whereSql .= " AND li.status = :status";
            $params[':status'] = $status;
        }
        if ($search) {
            $whereSql .= " AND (b.title LIKE :search OR s.full_name LIKE :search OR t.full_name LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $query = "
            SELECT li.*, b.title as book_title, b.author as book_author,
                   COALESCE(s.full_name, t.full_name, 'Unknown') as user_name
            FROM library_issues li
            JOIN library_books b ON li.book_id = b.id
            LEFT JOIN students s ON li.user_id = s.id AND li.user_type = 'student'
            LEFT JOIN teachers t ON li.user_id = t.id AND li.user_type IN ('teacher', 'staff')
            WHERE $whereSql
            ORDER BY li.issue_date DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $issues = $stmt->fetchAll();
        
        // Auto-refresh overdue status
        foreach ($issues as &$issue) {
            if ($issue['status'] === 'issued' && strtotime($issue['due_date']) < time()) {
                $issue['status'] = 'overdue';
                $db->prepare("UPDATE library_issues SET status = 'overdue' WHERE id = ?")->execute([$issue['id']]);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $issues]);
        exit;
    }

    if ($action === 'issue_book' && $method === 'POST') {
        $book_id = $input['book_id'] ?? null;
        $user_type = $input['user_type'] ?? 'student';
        $user_id = $input['user_id'] ?? null;
        $due_date = $input['due_date'] ?? date('Y-m-d', strtotime('+14 days'));
        
        if (!$book_id || !$user_id) throw new Exception("Book ID and User ID are required");
        
        $db->beginTransaction();
        
        // Check availability
        $stmtCheck = $db->prepare("SELECT available FROM library_books WHERE id = ? AND tenant_id = ? FOR UPDATE");
        $stmtCheck->execute([$book_id, $tenantId]);
        $book = $stmtCheck->fetch();
        if (!$book || $book['available'] <= 0) throw new Exception("Book is currently unavailable");
        
        // Issue
        $stmtIssue = $db->prepare("INSERT INTO library_issues (tenant_id, book_id, user_type, user_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, CURDATE(), ?, 'issued')");
        $stmtIssue->execute([$tenantId, $book_id, $user_type, $user_id, $due_date]);
        $newIssueId = $db->lastInsertId();
        
        // Decrement available
        $stmtDec = $db->prepare("UPDATE library_books SET available = available - 1 WHERE id = ?");
        $stmtDec->execute([$book_id]);
        
        \App\Helpers\AuditLogger::log('CREATE', 'library_issues', $newIssueId, null, $input);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Book issued successfully']);
        exit;
    }

    if ($action === 'return_book' && $method === 'POST') {
        $issue_id = $input['issue_id'] ?? null;
        $fine_amount = $input['fine_amount'] ?? 0;
        $new_status = $input['status'] ?? 'returned'; // can be 'lost'
        
        if (!$issue_id) throw new Exception("Issue ID required");
        
        $db->beginTransaction();
        
        $stmtCheck = $db->prepare("SELECT book_id, status FROM library_issues WHERE id = ? AND tenant_id = ? FOR UPDATE");
        $stmtCheck->execute([$issue_id, $tenantId]);
        $issue = $stmtCheck->fetch();
        
        if (!$issue) throw new Exception("Issue record not found");
        if (in_array($issue['status'], ['returned', 'lost'])) throw new Exception("Already returned or lost");
        
        $stmtUpdate = $db->prepare("UPDATE library_issues SET status = ?, return_date = CURDATE(), fine_amount = ? WHERE id = ?");
        $stmtUpdate->execute([$new_status, $fine_amount, $issue_id]);
        
        // Increment book availability if returned and not lost
        if ($new_status === 'returned') {
            $stmtInc = $db->prepare("UPDATE library_books SET available = available + 1 WHERE id = ?");
            $stmtInc->execute([$issue['book_id']]);
        } else {
            // If lost, reduce total stock quantity
            $stmtLoss = $db->prepare("UPDATE library_books SET quantity = quantity - 1 WHERE id = ?");
            $stmtLoss->execute([$issue['book_id']]);
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => "Book marked as $new_status"]);
        exit;
    }


    // ----------------------------------------------------
    // Stock Reports
    // ----------------------------------------------------
    if ($action === 'stock_report' && $method === 'GET') {
        $stmt = $db->prepare("
            SELECT category, COUNT(*) as unique_titles, 
                   SUM(quantity) as total_books, 
                   SUM(available) as books_available,
                   SUM(quantity - available) as books_issued,
                   SUM(price * quantity) as total_value
            FROM library_books 
            WHERE tenant_id = :tid AND deleted_at IS NULL
            GROUP BY category
            ORDER BY category
        ");
        $stmt->execute([':tid' => $tenantId]);
        $summary = $stmt->fetchAll();
        
        $stmtTotal = $db->prepare("SELECT SUM(quantity) as total_inventory FROM library_books WHERE tenant_id = :tid AND deleted_at IS NULL");
        $stmtTotal->execute([':tid' => $tenantId]);
        
        echo json_encode([
            'success' => true, 
            'data' => $summary, 
            'total_inventory' => $stmtTotal->fetchColumn()
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => "Invalid or unhandled action '{$action}'."]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("Library Controller Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
