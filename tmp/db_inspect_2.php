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

$tables = ['platform_settings', 'platform_payments', 'tenant_payments'];
foreach ($tables as $table) {
    getTableSchema($pdo, $table);
}
