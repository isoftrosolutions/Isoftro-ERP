<?php
/**
 * Migration Script: Update student_fee_summary fee_status enum
 * Adds 'overdue' to the allowed values to prevent "Failed to load students" error.
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    echo "Starting migration: Updating student_fee_summary.fee_status enum...\n";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'student_fee_summary'");
    if ($stmt->rowCount() === 0) {
        echo "Error: Table 'student_fee_summary' does not exist. Skipping.\n";
        exit(1);
    }

    // Alter the table to include 'overdue' in the enum
    $sql = "ALTER TABLE student_fee_summary 
            MODIFY COLUMN fee_status ENUM('paid','unpaid','partial','overdue','no_fees') 
            NOT NULL DEFAULT 'unpaid'";
    
    $db->exec($sql);
    
    echo "Success: student_fee_summary table updated successfully.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
