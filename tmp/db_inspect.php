<?php
require_once 'c:/Apache24/htdocs/erp/config/config.php';

function getTableSchema($pdo, $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

$pdo = getDBConnection();

// List all tables
echo "--- ALL TABLES ---\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo $table . "\n";
}
echo "\n";

// Get schema for key tables
$keyTables = ['tenants', 'users', 'modules', 'institute_modules', 'subscriptions', 'billing', 'revenue'];
foreach ($keyTables as $table) {
    if (in_array($table, $tables)) {
        getTableSchema($pdo, $table);
    } else {
        echo "Table $table does not exist.\n\n";
    }
}
