<?php
/**
 * Date Conversion API Controller
 * Converts between AD (Gregorian) and BS (Bikram Sambat) dates
 * 
 * GET /api/admin/date-convert?date=YYYY-MM-DD&type=ad|bs
 *   type=ad  → input is AD, returns BS
 *   type=bs  → input is BS, returns AD
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$date = $_GET['date'] ?? '';
$type = $_GET['type'] ?? '';

if (empty($date) || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Both "date" and "type" parameters are required']);
    exit;
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

try {
    $result = '';
    $formatted = '';

    if ($type === 'ad') {
        // AD → BS
        $result = \App\Helpers\DateUtils::adToBs($date);
        $formatted = \App\Helpers\DateUtils::formatNepali($date, 'np');
    } elseif ($type === 'bs') {
        // BS → AD
        $result = \App\Helpers\DateUtils::bsToAd($date);
    } else {
        echo json_encode(['success' => false, 'message' => 'Type must be "ad" or "bs"']);
        exit;
    }

    if (empty($result)) {
        echo json_encode(['success' => false, 'message' => 'Conversion failed — date may be out of supported range']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'input' => $date,
        'type' => $type,
        'converted' => $result,
        'formatted' => $formatted
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Conversion error: ' . $e->getMessage()]);
}
