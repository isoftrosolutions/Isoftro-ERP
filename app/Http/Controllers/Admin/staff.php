<?php
/**
 * Staff API Controller (Wrapper)
 * Delegates to class-based StaffController
 */

require_once __DIR__ . '/StaffController.php';

use App\Http\Controllers\Admin\StaffController;

try {
    $controller = new StaffController();
    $result = $controller->handle();
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
