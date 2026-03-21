<?php
/**
 * Accounting API Controller (Wrapper)
 * Delegates to class-based AccountingController
 */

require_once __DIR__ . '/AccountingController.php';

use App\Http\Controllers\Admin\AccountingController;

try {
    $controller = new AccountingController();
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
