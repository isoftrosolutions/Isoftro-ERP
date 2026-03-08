<?php
/**
 * Diagnostic Script for Study Materials Module
 */
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain');

try {
    $db = getDBConnection();
    echo "Database connection successful.\n";

    $tables = ['study_materials', 'study_material_categories', 'study_material_permissions', 'study_material_access_logs'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' exists.\n";
            // Check columns
            $stmt = $db->query("DESCRIBE $table");
            echo "Columns in '$table':\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo " - {$row['Field']} ({$row['Type']})\n";
            }
        } else {
            echo "Table '$table' DOES NOT EXIST.\n";
        }
    }

    // Test count
    $stmt = $db->query("SELECT COUNT(*) FROM study_materials");
    echo "Total rows in 'study_materials': " . $stmt->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
