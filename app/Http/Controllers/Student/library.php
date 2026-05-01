<?php
/**
 * Student Library API
 * Handles library book viewing for students
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

$user = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$studentId = $_SESSION['userData']['student_id'] ?? null;

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$action = $_GET['action'] ?? 'borrowed';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'borrowed':
            // Get currently borrowed books
            $stmt = $db->prepare("
                SELECT lb.*, b.title as book_title, b.author, b.isbn, b.category,
                       DATEDIFF(lb.due_date, CURDATE()) as days_remaining
                FROM library_borrowings lb
                JOIN library_books b ON lb.book_id = b.id
                WHERE lb.student_id = :sid
                  AND lb.tenant_id = :tid
                  AND lb.status = 'borrowed'
                  AND lb.returned_at IS NULL
                ORDER BY lb.due_date ASC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $borrowed = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate fines for overdue books
            foreach ($borrowed as &$book) {
                if ($book['days_remaining'] < 0) {
                    $daysOverdue = abs($book['days_remaining']);
                    // Assuming fine rate is 5 per day (configurable)
                    $book['fine_amount'] = $daysOverdue * 5;
                    $book['is_overdue'] = true;
                } else {
                    $book['fine_amount'] = 0;
                    $book['is_overdue'] = false;
                }
            }
            
            // Get borrowing history
            $stmt = $db->prepare("
                SELECT lb.*, b.title as book_title, b.author, b.isbn
                FROM library_borrowings lb
                JOIN library_books b ON lb.book_id = b.id
                WHERE lb.student_id = :sid
                  AND lb.tenant_id = :tid
                  AND lb.status = 'returned'
                ORDER BY lb.returned_at DESC
                LIMIT 10
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'borrowed' => $borrowed,
                    'history' => $history,
                    'total_borrowed' => count($borrowed),
                    'overdue_count' => count(array_filter($borrowed, fn($b) => $b['is_overdue']))
                ]
            ]);
            break;
            
        case 'search':
            // Search books in library catalog
            $query = $_GET['q'] ?? '';
            $category = $_GET['category'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $offset = ($page - 1) * $perPage;
            
            $whereClause = "tenant_id = :tid AND status = 'available'";
            $params = ['tid' => $tenantId];
            
            if ($query) {
                $whereClause .= " AND (title LIKE :q OR author LIKE :q OR isbn LIKE :q)";
                $params['q'] = '%' . $query . '%';
            }
            
            if ($category) {
                $whereClause .= " AND category = :cat";
                $params['cat'] = $category;
            }
            
            // Get total count
            $stmt = $db->prepare("SELECT COUNT(*) FROM library_books WHERE $whereClause");
            $stmt->execute($params);
            $totalCount = (int)$stmt->fetchColumn();
            
            // Get books
            $sql = "
                SELECT b.*, 
                       CASE WHEN lb.id IS NOT NULL AND lb.status = 'borrowed' THEN 0 ELSE 1 END as is_available
                FROM library_books b
                LEFT JOIN library_borrowings lb ON b.id = lb.book_id 
                    AND lb.status = 'borrowed' AND lb.returned_at IS NULL
                WHERE b.$whereClause
                ORDER BY b.title ASC
                LIMIT :limit OFFSET :offset
            ";
            
            $params['limit'] = $perPage;
            $params['offset'] = $offset;
            
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(":$key", $value, $type);
            }
            $stmt->execute();
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get categories for filter
            $stmt = $db->prepare("
                SELECT DISTINCT category FROM library_books 
                WHERE tenant_id = :tid AND category IS NOT NULL
                ORDER BY category ASC
            ");
            $stmt->execute(['tid' => $tenantId]);
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'data' => $books,
                'categories' => $categories,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage)
                ]
            ]);
            break;
            
        case 'book_detail':
            $bookId = $_GET['book_id'] ?? null;
            
            if (!$bookId) {
                echo json_encode(['success' => false, 'message' => 'Book ID required']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT b.*, 
                       CASE WHEN lb.id IS NOT NULL AND lb.status = 'borrowed' THEN 0 ELSE 1 END as is_available,
                       lb.due_date as next_available_date
                FROM library_books b
                LEFT JOIN library_borrowings lb ON b.id = lb.book_id 
                    AND lb.status = 'borrowed' AND lb.returned_at IS NULL
                WHERE b.id = :bid AND b.tenant_id = :tid
            ");
            $stmt->execute(['bid' => $bookId, 'tid' => $tenantId]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$book) {
                echo json_encode(['success' => false, 'message' => 'Book not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'data' => $book]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Student Library Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'code' => 'DB_ERROR'
    ]);
    } catch (Exception $e) {
    error_log("Student Library Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'code' => 'GENERAL_ERROR'
    ]);
    }
