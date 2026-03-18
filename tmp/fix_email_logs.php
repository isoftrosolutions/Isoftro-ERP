<?php
require_once __DIR__ . '/../config/config.php';
$db = getDBConnection();

echo "Updating email_logs table...\n";

// Add campaign_id if missing
try {
    $db->query("SELECT campaign_id FROM email_logs LIMIT 1");
} catch (Exception $e) {
    echo "Adding campaign_id to email_logs...\n";
    $db->exec("ALTER TABLE email_logs ADD COLUMN campaign_id INT DEFAULT 0 AFTER student_id");
}

// Add sent_via if missing
try {
    $db->query("SELECT sent_via FROM email_logs LIMIT 1");
} catch (Exception $e) {
    echo "Adding sent_via to email_logs...\n";
    $db->exec("ALTER TABLE email_logs ADD COLUMN sent_via ENUM('tenant_smtp', 'system_smtp') DEFAULT 'system_smtp' AFTER status");
}

echo "Done.\n";
