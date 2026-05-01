<?php
/**
 * Teacher Payments API
 * Returns salary slips and payment history for the logged-in teacher
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
$role = $user['role'] ?? '';
$tenantId = $user['tenant_id'] ?? null;
$userId = $user['id'] ?? null;

try {
    $db = getDBConnection();

    // Fetch salary slips/payments from staff_salaries table
    // Note: Table uses user_id, which we have in $userId
    $stmt = $db->prepare("
        SELECT id, amount, month, year, payment_date, status, payment_method, transaction_id, remarks
        FROM staff_salaries
        WHERE user_id = :uid AND tenant_id = :tid
        ORDER BY year DESC, month DESC
    ");
    $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $payments
    ]);

} catch (PDOException $e) {
    error_log("Teacher Payments Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } catch (Exception $e) {
    error_log("Teacher Payments Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
