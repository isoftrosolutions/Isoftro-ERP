<?php
require_once 'c:/Apache24/htdocs/erp/config/config.php';
require_once 'c:/Apache24/htdocs/erp/vendor/autoload.php';

function getTableSchema($db, $table) {
    echo "\nSchema for table: $table\n";
    try {
        $stmt = $db->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            printf("%-20s %-20s %-10s %-5s %-10s %-20s\n", 
                $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default'], $row['Extra']);
        }
    } catch (Exception $e) {
        echo "Error describing $table: " . $e->getMessage() . "\n";
    }
}

try {
    $db = getDBConnection();
    getTableSchema($db, 'tenants');
    getTableSchema($db, 'students');
    getTableSchema($db, 'fee_records');
    getTableSchema($db, 'payment_transactions');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
