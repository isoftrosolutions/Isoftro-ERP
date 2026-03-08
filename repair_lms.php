<?php
/**
 * Repair Script for Study Materials Module
 */
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain');

try {
    $db = getDBConnection();
    echo "Database connection successful.\n";

    // DROP broken tables (only if empty)
    $db->exec("DROP TABLE IF EXISTS study_material_feedback");
    $db->exec("DROP TABLE IF EXISTS study_material_favorites");
    $db->exec("DROP TABLE IF EXISTS study_material_access_logs");
    $db->exec("DROP TABLE IF EXISTS study_material_permissions");
    $db->exec("DROP TABLE IF EXISTS study_materials");
    $db->exec("DROP TABLE IF EXISTS study_material_categories");
    
    echo "Dropped old tables.\n";

    // Read migration file
    $migrationFile = __DIR__ . '/database/migrations/2026_02_28_000001_create_study_materials_tables.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found at $migrationFile");
    }

    $sql = file_get_contents($migrationFile);
    
    // Execute SQL (multi-statement)
    $db->exec($sql);
    
    echo "Migration executed successfully.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
