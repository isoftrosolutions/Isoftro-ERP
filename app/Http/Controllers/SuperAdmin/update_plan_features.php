<?php
/**
 * API: Update Plan Features
 */
require_once __DIR__ . '/../../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getCurrentUser()['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$plan_id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$features_raw = $_POST['features'] ?? '';

if (!$plan_id) {
    echo json_encode(['success' => false, 'message' => 'Plan ID required']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    // Update plan basic info
    $stmt = $db->prepare("UPDATE subscription_plans SET name = :name, price_monthly = :price WHERE id = :pid");
    $stmt->execute(['name' => $name, 'price' => $price, 'pid' => $plan_id]);

    // Update features: delete all and re-insert (cleanest for multi-line textarea)
    $stmt = $db->prepare("DELETE FROM plan_features WHERE plan_id = :pid");
    $stmt->execute(['pid' => $plan_id]);

    $features = explode("\n", str_replace("\r", "", $features_raw));
    $sort_order = 1;
    foreach ($features as $f) {
        $f = trim($f);
        if (empty($f)) continue;

        $stmt = $db->prepare("INSERT INTO plan_features (plan_id, feature_text, sort_order) VALUES (:pid, :txt, :ord)");
        $stmt->execute(['pid' => $plan_id, 'txt' => $f, 'ord' => $sort_order++]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
