<?php
/**
 * iSoftro ERP — Authentication Endpoint
 * Handles secure login requests via AJAX
 */

require_once __DIR__ . '/../../config/config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$username = sanitizeInput($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

// Perform authentication
$authResult = authenticateUser($username, $password);

if ($authResult['success']) {
    // Generate session
    login($authResult['user']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'role' => $authResult['user']['role'],
        'user' => $authResult['user']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $authResult['message']
    ]);
}
?>
