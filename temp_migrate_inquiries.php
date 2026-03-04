<?php
require_once 'config/config.php';

try {
    $db = getDBConnection();
    
    // Check if deleted_at exists
    $stmt = $db->query("SHOW COLUMNS FROM inquiries LIKE 'deleted_at'");
    if (!$stmt->fetch()) {
        echo "Adding deleted_at column...\n";
        $db->exec("ALTER TABLE inquiries ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at");
        echo "Column added.\n";
    } else {
        echo "Column deleted_at already exists.\n";
    }

    // Update status enum
    echo "Updating status enum...\n";
    $db->exec("ALTER TABLE inquiries MODIFY COLUMN status ENUM('pending', 'follow_up', 'converted', 'closed', 'lost') DEFAULT 'pending'");
    echo "Status enum updated.\n";
    
    echo "Database migration complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
