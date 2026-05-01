<?php
require_once __DIR__ . '/ExpenseController.php';

use App\Http\Controllers\Admin\ExpenseController;

try {
    $controller = new ExpenseController();
    $result = $controller->handle();
    
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
exit;
